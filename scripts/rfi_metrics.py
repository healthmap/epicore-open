import datetime

import pandas as pd

_MONTHS = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
]
_ORGS = {
    1: "HealthMap",
    2: "Tephinet",
    3: "Ending Pandemics",
    4: "ProMed",
    5: "EpiCore",
    6: "MSF - Spain",
    7: "GeoSentinel",
}


def load_env(path):
    """Load environment variables from .env."""
    with open(path) as f:
        lines = f.readlines()
    pairs = [x.split("=") for x in lines]
    env = {k: v.replace("\n", "") for k, v in pairs}
    try:
        return env['DATA_DIR'], env['IMAGE_DIR'], env['SAVE_DATA_DIR']
    except KeyError as e:
        print("Environment variable missing:")
        print(e)


_, _, _SAVE_DATA_DIR = load_env("../.env")


def clean_dates(df):
    """Convert dates to datetime and add reaction time."""
    date_cols = [x for x in df.columns if "_date" in x]
    for col in date_cols:
        df[col] = pd.to_datetime(df[col], errors="coerce")
    df["reaction_time"] = df["first_response_date"] - df["iso_create_date"]
    return df


def clean_countries(df):
    """Clean names and fix repeats."""
    # strip leading and trail spaces in country name
    df["country"] = df["country"].str.strip()
    # fix repeat countries
    df = df.replace({"United States": "USA"})
    return df


def get_last_month_year():
    """Return last month and associated year as ints."""
    today = datetime.date.today()
    first_day_of_month = today.replace(day=1)
    last_day_last_month = first_day_of_month - datetime.timedelta(days=1)
    last_month = last_day_last_month.month
    year = last_day_last_month.year
    return last_month, year


def get_masks(df, dt_col):
    """Get boolean masks for filtering by timeframe."""
    last_month, year = get_last_month_year()

    last_month = (df[dt_col].dt.month == last_month) & (
        df[dt_col].dt.year == year)
    ytd = df[dt_col].dt.year == year
    last_year = df[dt_col].dt.year == (year - 1)

    return last_month, ytd, last_year


def get_response_str(td):
    """Convert timedelta to days/hours/minutes string."""
    if not isinstance(td, datetime.timedelta):
        return "N/A"
    days, hours, minutes = td.days, td.seconds // 3600, (td.seconds // 60) % 60
    rt_list = []
    if days != 0:
        rt_list.append("{} days".format(days))
    rt_list.append("{}h".format(hours))
    rt_list.append("{}min".format(minutes))
    return " ".join(rt_list)


def create_closed_rfis(df):
    """Create and save closed RFIs report."""
    last_month, year = get_last_month_year()
    mask = (df.action_date.dt.month == last_month) & (
        df.action_date.dt.year == year)
    actions_last_month = df.loc[mask, [
        "action_date", "status", "outcome"]].copy()

    closed_rfis_df = (
        actions_last_month
        .loc[actions_last_month.status == "C"]
        .copy()
    )
    total_closed = closed_rfis_df.shape[0]

    verified = closed_rfis_df.outcome.apply(
        lambda x: 1 if "Verified" in x else 0).sum()
    updated = closed_rfis_df.outcome.apply(
        lambda x: 1 if "Updated" in x else 0).sum()
    unverified = closed_rfis_df.outcome.apply(
        lambda x: 1 if "Unverified" in x else 0
    ).sum()

    categories = ["Verified (+/-)", "Updated (+/-)",
                  "Verified+Updated", "Unverified"]
    counts = [verified, updated, verified + updated, unverified]
    percents = [round((float(x) / float(total_closed)) * 100, 1)
                if total_closed != 0 else 0.00 for x in counts]

    report = pd.DataFrame(
        {"Outcome": categories, "Closed ({})".format(
            total_closed): counts, "Percent": percents}
    )

    report.to_html(_SAVE_DATA_DIR + "closed_rfis.html", index=False)
    return report


def create_opened_rfis(df):
    """Create and save opened RFIs report."""
    last_month, _, _ = get_masks(df, "create_date")

    opened_rfis_df = df.loc[last_month].copy()
    total_opened_rfis = opened_rfis_df.shape[0]

    report = opened_rfis_df["organization_id"].value_counts().reset_index()
    missing_index = [x for x in [5, 7, 1, 6, 4] if x not in list(report['index'])]
    missing_oid = [0 for x in missing_index]
    placeholders = pd.DataFrame({
        "index": missing_index,
        "organization_id": missing_oid
    })
    report = pd.concat([report, placeholders], axis=0).sort_values("index")

    opened_count_col = "Opened ({})".format(total_opened_rfis)
    report.columns = ["Organization", opened_count_col]

    report["Organization"] = report["Organization"].map(_ORGS)

    report["Percent"] = report[opened_count_col].apply(
        lambda x: round((float(x) / float(total_opened_rfis)) * 100, 1)
    )

    report.to_html(_SAVE_DATA_DIR + "opened_rfis.html", index=False)
    return report


def get_stats_from_mask(df, mask, time_frame):
    """Get dictionary of response stats for time frame."""
    mask_closed = df.status == "C"
    mask_responded = ~df["first_response_date"].isna()

    less_than_24 = float((
        df.loc[mask, "reaction_time"] < datetime.timedelta(hours=24))
        .sum()
    )
    closed = float(df.loc[mask_closed & mask].shape[0])
    responded = float(df.loc[mask_responded & mask].shape[0])
    if closed == 0:
        response_rate = "N/A"
    else:
        response_rate = "{:.1%}".format(responded / closed)
    # Exclude minimum and maximum values from avg response time calc
    rt_df = df.loc[mask].copy()
    min_rt = rt_df.reaction_time.min()
    max_rt = rt_df.reaction_time.max()
    mask_trunc = (rt_df.reaction_time > min_rt) & (rt_df.reaction_time < max_rt)
    response_time = get_response_str(rt_df.loc[mask_trunc, "reaction_time"].mean())
    if responded == 0:
        responded_in_24 = "N/A"
    else:
        responded_in_24 = "{:.1%}".format(less_than_24 / responded)

    return {
        "Time Frame": time_frame,
        "Closed": int(closed),
        "Responded": int(responded),
        "Response Rate": response_rate,
        "Response Time": response_time,
        "Responded in 24hrs": responded_in_24,
    }


def create_rfi_response_metrics(df):
    """Create and save RFI response report."""
    last_month, year = get_last_month_year()
    month_str = _MONTHS[last_month - 1]

    mask_last_month, mask_ytd, mask_last_year = get_masks(df, "action_date")

    report = pd.DataFrame(
        [
            get_stats_from_mask(df, mask_last_month,
                                "{} {}".format(month_str, year)),
            get_stats_from_mask(df, mask_ytd, str(year)),
            get_stats_from_mask(df, mask_last_year, str(year - 1)),
        ]
    )
    report.to_html(_SAVE_DATA_DIR + "rfi_response_metrics.html", index=False)
    return report


def get_verification_from_mask(df, mask):
    """Create verified/unverified aggs for time frame."""
    is_verified = ["Verified (+)", "Verified (-)",
                   "Updated (+)", "Updated (-)"]
    df = df[mask].copy()

    if(df.shape[0] == 0):
        unverified_report = pd.DataFrame(columns=["Country", "Unverified"])
        verified_report = pd.DataFrame(
            columns=["Country", "RFIs", "Verified", "Verification Rate (%)"])
        return unverified_report, verified_report
    df = df[["country", "outcome"]].copy()
    df.columns = ["Country", "Outcome"]

    df["Verified"] = df["Outcome"].apply(
        lambda x: 1 if x in is_verified else 0)
    df["Unverified"] = df["Outcome"].apply(
        lambda x: 1 if x == "Unverified" else 0)
    df["RFIs"] = 1

    report = (
        df[["Verified", "Unverified", "RFIs"]]
        .groupby(df["Country"])
        .sum()
        .reset_index()
        .sort_values("Country")
    )

    report["Verification Rate (%)"] = (
        report["Verified"].astype(float) / report["RFIs"].astype(float)
    )
    report["Verification Rate (%)"] = report["Verification Rate (%)"].apply(
        lambda x: round(float(x) * 100, 1))

    unverified_report = report.loc[
        report.Unverified > 0, ["Country", "Unverified"]
    ].copy()
    # Note: The Verified report
    verified_report = (
        report.loc[
            (report.Verified > 0) & (report.Verified < 40) & (report.RFIs > 4),
            ["Country", "RFIs", "Verified", "Verification Rate (%)"],
        ]
        .copy()
        .sort_values("Verification Rate (%)")
    )

    return unverified_report, verified_report


def create_country_verified(df):
    """Create and save verified/unverified reports."""
    mask_last_month, mask_ytd, mask_last_year = get_masks(df, "action_date")

    unverified_last_month, _ = get_verification_from_mask(df, mask_last_month)
    _, low_verified_last_year = get_verification_from_mask(df, mask_last_year)
    _, low_verified_ytd = get_verification_from_mask(df, mask_ytd)

    unverified_last_month.to_html(
        _SAVE_DATA_DIR + "rfi_country_unverified.html", index=False
    )
    low_verified_last_year.to_html(
        _SAVE_DATA_DIR + "rfi_ver_country.html", index=False)
    low_verified_ytd.to_html(
        _SAVE_DATA_DIR + "rfi_ver_country_ytd.html", index=False)
    return unverified_last_month, low_verified_last_year, low_verified_ytd


def main():
    """Main function for generating all reports."""
    rfistats = pd.read_csv(_SAVE_DATA_DIR + "rfistats.csv", encoding="utf-8")
    rfistats = clean_dates(rfistats)
    rfistats = clean_countries(rfistats)
    create_closed_rfis(rfistats)
    create_opened_rfis(rfistats)
    create_rfi_response_metrics(rfistats)
    create_country_verified(rfistats)


if __name__ == "__main__":
    main()
