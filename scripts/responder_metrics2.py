import datetime

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from pandas.plotting import register_matplotlib_converters
register_matplotlib_converters()


def clean_pair(pair):
    if len(pair) > 2:
        pair = [pair[0], "=".join(pair[1:])]
        return pair
    elif len(pair) <= 1:
        raise TypeError(
            "Pair must be an iterable of length 2. Is there a blank row in your .env file?")
    return pair


def load_env(path):
    """Load environment variables from .env."""
    with open(path) as f:
        lines = f.readlines()
    pairs = [x.split("=") for x in lines]
    pairs = [clean_pair(p) for p in pairs]
    env = {k: v.replace("\n", "") for k, v in pairs}
    try:
        return env['DATA_DIR'], env['IMAGE_DIR'], env['SAVE_DATA_DIR']
    except KeyError as e:
        print("Environment variable missing:")
        print(e)


_DATA_DIR, _IMAGE_DIR, _SAVE_DATA_DIR = load_env("../.env")


def load_population():
    """Load and clean population data."""
    population = pd.read_csv(
        _DATA_DIR + "population_2018.csv", encoding="ISO-8859-1")
    # fix for Namibia (country code NA)
    population["country_code"].fillna("NA", inplace=True)
    return population


def load_country_codes():
    """Load and clean UN Country Codes."""
    un_codes = pd.read_csv(
        _DATA_DIR + "un_countrycode_new.csv", encoding="ISO-8859-1")
    un_codes = un_codes.fillna("NA")
    return un_codes


def load_members():
    """Load and clean members approval data."""
    members = pd.read_csv(_DATA_DIR + "approval.csv")
    # clean up data column names
    members.columns = (
        members.columns.to_series()
        .str.strip()
        .str.lower()
        .str.replace(" ", "_", regex=False)
        .str.replace("(", "", regex=False)
        .str.replace(")", "", regex=False)
    )
    # format dates for sorting
    members["application_date"] = pd.to_datetime(members["application_date"])
    members["approval_date"] = pd.to_datetime(members["approval_date"])
    members["acceptance_date"] = pd.to_datetime(members["acceptance_date"])
    # convert ids to int, set id NA values to 0
    members["member_id"].fillna("0", inplace=True)
    members["member_id"] = members.member_id.astype(int)
    # fix for Namibia (country code NA)
    members["country_code"].fillna("NA", inplace=True)
    return members


def process_apps_by_date(members):
    """Build applicants by date aggregates."""
    # group and sort applicants and approved members by date
    apps_by_date = (
        members.groupby(["application_date"])
        .application_date.count()
        .reset_index(name="applicants")
        .sort_values(["application_date"])
    )
    return apps_by_date


def process_exp(members):
    """Build member counts with health experience."""
    approved = members[members.approval_date.notnull()]
    exp = (
        approved.groupby(["health_experience"])
        .health_experience.count()
        .reset_index(name="count")
    )

    # total experience
    total_exp = exp["count"].sum()
    # add column for percent values
    exp["percent"] = exp["count"] * 100 / total_exp
    exp.percent = exp.percent.round().astype(int)
    exp.columns = ["Experience", "Members", "%"]
    return exp


def process_members(members, population, un_codes):
    """Build merged dataframes split by members/no members."""
    approved = members.loc[members["user_status"] == "Approved"].copy()
    app_country = (
        approved.groupby(["country", "country_code", "who_region"])
        .country.count()
        .reset_index(name="applicants")
        .sort_values(["country"])
    )
    # merge country and population tables
    app_country_pop = app_country.merge(
        population.drop("country", axis=1), how="left", on="country_code"
    )

    # merge with 3 letter UN country codes
    mem_countries = app_country_pop.merge(
        un_codes.drop("country", axis=1), how="left", on="country_code"
    )

    # include members density (responders per 1 million population) and sort by density
    mem_countries["member_density"] = (
        1000 * mem_countries["applicants"] / mem_countries["population"]
    ).round(2)
    mem_countries = mem_countries.sort_values(["member_density"])

    # get countries with no members
    all_country = un_codes.merge(
        app_country.drop("country", axis=1), how="left", on="country_code"
    )
    all_country.sort_values(["applicants"], inplace=True)
    mask = all_country["applicants"].isnull()
    no_mem_countries = all_country.loc[mask].copy()
    no_mem_countries = no_mem_countries[["country", "un_country_code"]]
    no_mem_countries = no_mem_countries[no_mem_countries.un_country_code.notnull(
    )]
    return mem_countries, no_mem_countries


def process_new_region(members):
    """Build members by WHO region dataframe."""
    approved = members.loc[members["user_status"] == "Approved"].copy()
    # group by region, count members in regions, and sort by region
    app_region = (
        approved.groupby(["who_region"])
        .who_region.count()
        .reset_index(name="applicants")
        .sort_values(["who_region"])
    )

    # re-arrange regions
    central_america = app_region[
        app_region["who_region"].str.lower().str.contains("central america")
    ]
    central_america_total = central_america["applicants"].sum()
    app_region.at[5, "who_region"] = "Central-South America"
    app_region.at[5, "applicants"] = int(central_america_total)
    new_region = app_region.drop([3, 7])
    new_region.rename(columns={"who_region": "Region"}, inplace=True)
    return new_region


def get_masks(df, dt_col):
    """Get boolean masks for filtering by timeframe."""
    last_month, year = get_last_month_year()

    last_month = (df[dt_col].dt.month == last_month) & (
        df[dt_col].dt.year == year)
    ytd = df[dt_col].dt.year == year
    last_year = df[dt_col].dt.year == (year - 1)

    return last_month, ytd, last_year


def get_last_month_year():
    """Get last full month and associated year."""
    today = datetime.date.today()
    first_day_of_month = today.replace(day=1)
    last_day_last_month = first_day_of_month - datetime.timedelta(days=1)
    last_month = last_day_last_month.month
    year = last_day_last_month.year
    return last_month, year


def get_total_applicants_month(apps_by_date):
    """Get total count of applicants for last full month."""
    last_month, _, _ = get_masks(apps_by_date, "application_date")
    applicants_month = apps_by_date.loc[last_month].copy()
    total_apps = applicants_month["applicants"].sum()
    return total_apps


def get_approved_totals(members):
    """Get total count of approvals for all time and last month."""
    approved_by_date = (
        members.groupby(["approval_date"])
        .approval_date.count()
        .reset_index(name="approved")
        .sort_values(["approval_date"])
    )
    last_month, _, _ = get_masks(approved_by_date, "approval_date")
    approved_month = approved_by_date.loc[last_month].copy()

    tot_appr = approved_by_date["approved"].sum()
    tot_appr_month = approved_month["approved"].sum()
    return tot_appr, tot_appr_month


def get_comma_separated(codes):
    """Takes list of codes, returns comma separated string."""
    return ", ".join(codes)


def plot_experience(exp):
    """Save chart of members by health experience."""
    # get total human experience, filtered by "human"
    human_exp = exp[exp["Experience"].str.lower().str.contains("human")]
    total_human_exp = human_exp["%"].sum()
    # get total animal experience, filtered by "animal:
    animal_exp = exp[exp["Experience"].str.lower().str.contains("animal")]
    total_animal_exp = animal_exp["%"].sum()
    # get total environmental experience, filtered by "environmental"
    environmental_exp = exp[exp["Experience"].str.lower(
    ).str.contains("environmental")]
    total_environmental_exp = environmental_exp["%"].sum()
    # plot expertise bar chart
    fig2 = plt.figure()
    x = np.arange(3)
    y = [total_human_exp, total_animal_exp, total_environmental_exp]
    hm, an, en = plt.bar(x, y, align="center", edgecolor="none")
    hm.set_facecolor("#015163")
    an.set_facecolor("#3fc8c8")
    en.set_facecolor("#2e3335")
    plt.xticks(x, ("Human", "Animal", "Environmental"))
    plt.tick_params(
        top=False,
        bottom=False,
        left=False,
        right=False,
        labelleft=False,
        labelbottom=True,
        labelsize=14,
    )
    plt.box(False)
    # add value labels to bars
    for a, b in zip(x, y):
        plt.text(a, b + 2, str(b) + "%", ha="center",
                 color="#2e3335", fontsize=18)
    fig2.savefig(_IMAGE_DIR + "health_expertise.svg",
                 bbox_inches="tight", format="svg")


def plot_applicants(apps_by_date, year):
    """Save plot of applicants by date."""
    fig = plt.figure()
    plt.plot(
        apps_by_date.application_date,
        apps_by_date.applicants,
        color="#3fc8c8",
    )
    plt.tick_params(
        top=False,
        bottom=False,
        left=False,
        right=False,
        labelleft=True,
        labelbottom=True,
        labelsize=12,
        labelcolor="#878c8d",
    )
    plt.xticks(rotation=20)
    plt.gca().spines["right"].set_color("none")
    plt.gca().spines["top"].set_color("none")
    plt.gca().spines["left"].set_color("#d5d7d8")
    plt.gca().spines["bottom"].set_color("#d5d7d8")
    plt.xlim([datetime.date((year - 1), 1, 1), datetime.datetime.now()])
    plt.ylim([0, 10])
    fig.savefig(_IMAGE_DIR + "applicants_year.svg",
                format="svg", bbox_inches="tight")


def plot_applicants_last_month(apps_by_date):
    """Save plot of applicants by date for last month."""
    last_month, year = get_last_month_year()
    last_month_mask, _, _ = get_masks(apps_by_date, "application_date")
    applicants_plot = apps_by_date.loc[last_month_mask].copy()
    # generate plot
    fig = plt.figure()
    plt.plot(
        applicants_plot.application_date,
        applicants_plot.applicants,
        color="#3fc8c8",
    )
    plt.tick_params(
        top=False,
        bottom=False,
        left=False,
        right=False,
        labelleft=True,
        labelbottom=True,
        labelsize=12,
        labelcolor="#878c8d",
    )
    plt.xticks(rotation=20)
    plt.gca().spines["right"].set_color("none")
    plt.gca().spines["top"].set_color("none")
    plt.gca().spines["left"].set_color("#d5d7d8")
    plt.gca().spines["bottom"].set_color("#d5d7d8")
    plt.xlim([datetime.date(year, last_month, 1), datetime.datetime.now()])
    plt.ylim([0, 10])
    fig.savefig(_IMAGE_DIR + "applicants_month.svg",
                format="svg", bbox_inches="tight")


def create_experience_table(exp):
    """Export experience report."""
    exp.to_html(_SAVE_DATA_DIR + "experience_table.html", index=False)


def create_app_no_training_table(members):
    """Export accepted applicants w/ no training report."""
    today = datetime.datetime.now()
    two_months_ago = today - datetime.timedelta(days=60)
    mask = (
        (members["user_status"] == "Accepted")
        & (members["course_type"].isnull())
        & (members["acceptance_date"] > two_months_ago)
    )
    app_no_training = members.loc[mask].copy()
    app_no_training.rename(
        columns={"acceptance_date": "Acceptance Date",
                 "member_id": "members ID"},
        inplace=True,
    )
    app_no_training = app_no_training[["Acceptance Date", "members ID"]]
    app_no_training.to_html(
        _SAVE_DATA_DIR + "app_no_training_table.html", index=False)


def create_heard_about_table(members):
    """Export heard about Epicore report."""
    last_month_mask, _, _ = get_masks(members, "application_date")
    members_month = members.loc[
        last_month_mask,
    ].copy()
    members_month.rename(
        columns={"heard_about_epicore_by": "Source"}, inplace=True)
    heard_about = (
        members_month.groupby(["Source"])
        .Source.count()
        .reset_index(name="Applicants")
        .sort_values(["Source"])
    )
    heard_about.to_html(_SAVE_DATA_DIR + "heard_about_table.html", index=False)


def create_new_applicants_table(members):
    """Export new applicants report."""
    members = members[
        ["application_date", "approval_date", "country_code", "country"]
    ].copy()

    new_by_country = (
        members.groupby(["country", "approval_date"])
        .country.count()
        .reset_index(name="Members")
        .sort_values(["country"])
    )
    last_month_mask, _, _ = get_masks(new_by_country, "approval_date")
    new_last_month = new_by_country.loc[last_month_mask].copy()

    report = new_last_month.groupby(["country"]).sum().reset_index()
    report.to_html(_SAVE_DATA_DIR + "new_applicants_table.html", index=False)


def create_no_member_countries_table(no_mem_countries):
    """Export countries with no members report."""
    no_mem_countries.to_html(
        _SAVE_DATA_DIR + "no_applicants_countries_table.html", index=False
    )


def create_no_members_region_table(members, no_mem_countries, un_codes):
    """Export regions with no members report."""
    approved = members.loc[members["user_status"] == "Approved"].copy()
    # merge no members countries with regions
    no_member_regions = no_mem_countries.merge(
        un_codes, how="left", on="un_country_code"
    )
    no_member_regions = no_member_regions[
        ["country_x", "country_code", "un_country_code", "who_region"]
    ]
    no_member_regions.rename(
        columns={"country_x": "country", "who_region": "Region"}, inplace=True
    )

    # group by region
    no_members_reg = no_member_regions.groupby("Region").agg(
        {"un_country_code": get_comma_separated}
    )

    # Merge current approval data to the new regions list
    no_member_merged = approved.merge(un_codes, how="left", on="country_code")

    # Pick few columns for ease
    no_member_merged = no_member_merged[
        [
            "approval_date",
            "country_code",
            "un_country_code",
            "who_region_x",
            "who_region_y",
        ]
    ]

    # Rename Columns
    no_member_merged.rename(
        columns={
            "approval_date": "approval_date",
            "country_code": "country_code",
            "un_country_code": "un_country_code",
            "who_region_x": "who_region_old",
            "who_region_y": "Region",
        },
        inplace=True,
    )

    # Group by Region and sum up the total applicants from that region
    po_grouped = (
        no_member_merged.groupby(["Region"])
        .Region.count()
        .reset_index(name="applicants")
        .sort_values(["Region"])
    )

    # Finally Merge the above output to the list of NO members countries in the region
    report = po_grouped.merge(no_members_reg, how="left", on="Region")

    total_region_applicants = report.applicants.sum()

    report["Members(%)"] = (
        (100 * report["applicants"] /
         total_region_applicants).round().astype(int)
    )
    report.rename(
        columns={
            "applicants": "Members",
            "un_country_code": "Countries with No Members",
        },
        inplace=True,
    )
    report.fillna("", inplace=True)

    report.to_html(_SAVE_DATA_DIR +
                   "no_members_region_table.html", index=False)
    return report


def create_country_table(mem_countries):
    """Export applicants by country report."""
    app_country_density = mem_countries[
        ["country", "applicants", "member_density"]
    ].copy()
    app_country_density.rename(
        columns={"country": "Country", "applicants": "n",
                 "member_density": "Density"},
        inplace=True,
    )
    app_country_density.to_html(
        _SAVE_DATA_DIR + "country_table.html", index=False)


def create_region_table(new_region):
    """Export region table report."""
    # data frame for members by region merged with counties with no members
    # calculate %
    total_region_applicants = new_region.applicants.sum()
    new_region["Members(%)"] = (
        (100 * new_region["applicants"].astype(float) /
         float(total_region_applicants)).round(1)
    )
    new_region.rename(columns={"applicants": "Members"}, inplace=True)
    new_region.to_html(_SAVE_DATA_DIR + "region_table.html", index=False)


def create_membership_table(
    tot_appr,
    total_apps,
    tot_appr_month,
    total_member_countries,
):
    """Export membership summary report."""
    # creat data frame for memebership summary
    data = [
        ["Responders", tot_appr],
        ["New Applicants", str(total_apps)],
        ["New Responders", str(tot_appr_month)],
        ["Countries Represented", str(total_member_countries)],
    ]

    membership = pd.DataFrame(data, columns=["Metric", "Value"])
    membership.to_csv(_SAVE_DATA_DIR + "membership.csv", sep="|", index=False)
    membership.to_html(_SAVE_DATA_DIR + "membership.html", index=False)


def create_members_regions_table(total_member_countries, total_no_member_countries):
    """Export regions with/without members report."""
    data_region = [
        [
            "Countries and territories included: " +
            str(total_member_countries),
            "Countries and territories missing: " +
            str(total_no_member_countries),
        ]
    ]

    data_region = pd.DataFrame(data_region, columns=["", ""])
    data_region.to_html(_SAVE_DATA_DIR + "members_regions.html", index=False)


def main():
    """Main function for generating plots/reports."""
    population = load_population()
    un_codes = load_country_codes()
    members = load_members()

    exp = process_exp(members)
    apps_by_date = process_apps_by_date(members)
    mem_countries, no_mem_countries = process_members(
        members, population, un_codes)
    new_region = process_new_region(members)

    _, year = get_last_month_year()
    total_apps = get_total_applicants_month(apps_by_date)
    tot_appr, tot_appr_month = get_approved_totals(members)

    plot_experience(exp)
    plot_applicants(apps_by_date, year)
    plot_applicants_last_month(apps_by_date)

    create_experience_table(exp)
    create_app_no_training_table(members)
    create_heard_about_table(members)
    create_new_applicants_table(members)
    create_no_members_region_table(members, no_mem_countries, un_codes)
    create_country_table(mem_countries)
    create_region_table(new_region)
    create_membership_table(
        tot_appr,
        total_apps,
        tot_appr_month,
        mem_countries.shape[0],
    )
    create_members_regions_table(
        mem_countries.shape[0], no_mem_countries.shape[0])


if __name__ == "__main__":
    main()
