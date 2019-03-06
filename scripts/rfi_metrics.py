# Jeff Andre
# December, 26 2018
#
# Generates RFI tables on Responder RFI metrics dashboard.
#
# usage:
#
#  - no args: generates report for last month of data
#    rfi_metrics.py
#
#  - with args: generates report for month and year
#    rfi_metrics.py month year
#    where month = 1..12, year = 2018..2100
#
#
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import csv
import datetime
from datetime import timedelta
import pandas as pd
import numpy as np
import itertools
from pandas.plotting import table
import sys
import os
from decimal import Decimal

reload(sys)
sys.setdefaultencoding('utf-8')

pd.set_option('display.max_rows', 1000)

# set month and year
d = datetime.date.today()
year = d.year
month = d.month

# always use last month to get full month of data
if (month > 1):
    month = month -1
else:
    month = 12

# use args if available
if (len(sys.argv) == 3 ):
    arg_month = int(sys.argv[1])
    arg_year = int(sys.argv[2])
    if (arg_year >= 2016) and (arg_year <=2100) and (arg_month > 0) and (arg_month <=12):
        month = arg_month
        year = arg_year

last_year = year -1

# next month
next_month = month +1
if month == 12:
    next_month = 1
    
months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
organizations = { 1: 'HealthMap', 2: 'Tephinet', 3: 'Ending Pandemics', 4: 'ProMed', 5: 'EpiCore', 6: 'MSF - Spain', 7: 'Geosentinel' }

data_dir = '/var/www/html/prod.epicore.org/data/'
image_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'img/metrics')) +'/';
save_data_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'data')) +'/';


# read rfi stats
rfistats_df = pd.read_csv(data_dir + 'rfistats.csv', encoding = "utf-8")
# clean up data column names
rfistats_df.columns = rfistats_df.columns.to_series().str.strip().str.lower().str.replace(' ', '_').str.replace('(', '').str.replace(')', '')
# format dates
rfistats_df['create_date'] = pd.to_datetime(rfistats_df['create_date'])
rfistats_df['iso_create_date'] = pd.to_datetime(rfistats_df['iso_create_date'])
rfistats_df['first_response_date'] = pd.to_datetime(rfistats_df['first_response_date'])
rfistats_df['event_date'] = pd.to_datetime(rfistats_df['event_date'], errors='coerce')

#get all closed rfis
mask = rfistats_df['status'] == 'C'
rfi_all_closed_df = rfistats_df.loc[mask]


#get RFIs for last full month
start_year = year
if next_month == 1:
    start_year = year-1
#print( datetime.date(start_year, month, 1))
#print(datetime.date(year, next_month, 1))
mask = (rfistats_df['create_date'] > pd.Timestamp(datetime.date(start_year, month, 1)) ) & (rfistats_df['create_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
rfi_month_df = rfistats_df.loc[mask]
total_rfi_month = len(rfi_month_df)

# get RFIs for last month for each organization
# 1 - HealthMap
# 2 - Tephinet
# 3 - Ending Pandemics
# 4 - ProMed
# 5 - EpiCore
# 6 - MSF - Spain
# 7 - Geosentinel
#print(rfi_month_df[['outcome','create_date','organization_id', 'country']])

rfi_healthmap = rfi_month_df[rfi_month_df['organization_id'] == 1]
total_rfi_healthmap = len(rfi_healthmap)
rfi_tephinet = rfi_month_df[rfi_month_df['organization_id'] == 2]
total_rfi_tephinet = len(rfi_tephinet)
rfi_endingpandemics = rfi_month_df[rfi_month_df['organization_id'] == 3]
total_rfi_endingpandemics = len(rfi_endingpandemics)
rfi_promed = rfi_month_df[rfi_month_df['organization_id'] == 4]
total_rfi_promed = len(rfi_promed)
rfi_epicore = rfi_month_df[rfi_month_df['organization_id'] == 5]
total_rfi_epicore = len(rfi_epicore)
rfi_msf = rfi_month_df[rfi_month_df['organization_id'] == 6]
total_rfi_msf = len(rfi_msf)
rfi_geosentinel = rfi_month_df[rfi_month_df['organization_id'] == 7]
total_rfi_geosentinel = len(rfi_geosentinel)

rfi_month_country_df =rfi_month_df[['outcome','create_date','organization_id', 'country']].country.unique()
#rfi_month_unique_country_df = rfi_month_country_df.country.unique()
total_rfi_month_country = len(rfi_month_country_df)
#print(total_rfi_month_country)



# get closed RFIs for last full month
mask = rfi_month_df['status'] == 'C'
rfi_closed_month_df = rfi_month_df.loc[mask]
total_closed_month = len(rfi_closed_month_df)

# get verified (+/-) for last full month
rfi_verified_month_df = rfi_closed_month_df[rfi_closed_month_df['outcome'].str.contains("Verified")]
total_verified_month = len(rfi_verified_month_df)

# get updated (+/-) for last full month
rfi_updated_month_df = rfi_closed_month_df[rfi_closed_month_df['outcome'].str.contains("Updated")]
total_updated_month = len(rfi_updated_month_df)

# get unverified for last full month
rfi_unverified_month_df = rfi_closed_month_df[rfi_closed_month_df['outcome'].str.contains("Unverified")]
total_unverified_month = len(rfi_unverified_month_df)

# print results
#print(rfi_closed_month_df[['outcome','create_date','organization_id']])
#print(rfi_verified_month_df[['outcome','create_date','organization_id']])
#print(rfi_unverified_month_df[['outcome','create_date','organization_id']])

# Print results for RFIs for the month
#print('Opened RFIs: ' + str(total_rfi_month))
#print('EpiCore: ' + str(total_rfi_epicore))
#print('HealthMap: ' + str(total_rfi_healthmap))
#print('MSF Spain (OCBA):' + str(total_rfi_msf))
#print('ProMED: ' + str(total_rfi_promed))
#print('Ending Pandemics: ' + str(total_rfi_endingpandemics))
#print('Tephinet: ' + str(total_rfi_tephinet))
#print('Geosentinel: ' + str(total_rfi_geosentinel))
#print('Countries involved in RFIs: ' + str(total_rfi_month_country))

# create data frame for Opened (all open and closed) RFIs for the month
data = [['EpiCore', str(total_rfi_epicore), str(round(Decimal(100*float(total_rfi_epicore)/total_rfi_month,1))) ], \
['GeoSentinel', str(total_rfi_geosentinel), str(round(Decimal(100*float(total_rfi_geosentinel)/total_rfi_month,2)))], \
['HealthMap', str(total_rfi_healthmap), str(round(Decimal(100*float(total_rfi_healthmap)/total_rfi_month,1))) ], \
['MSF Spain (OCBA)', str(total_rfi_msf), str(round(Decimal(100*float(total_rfi_msf)/total_rfi_month,1))) ], \
['ProMED', str(total_rfi_promed), str(round(Decimal(100*float(total_rfi_promed)/total_rfi_month,1)))]
 ]

opened_rfis_df = pd.DataFrame(data, columns=['Opened RFIs', str(total_rfi_month), '  %  '])
opened_rfis_df.to_html(save_data_dir + 'opened_rfis.html', index=False)

# create data frame for closed RFIs for the month
data = [['Verified (+/-)', str(total_verified_month), str(round(Decimal(100*float(total_verified_month))/total_closed_month,1)) ], \
['Updated (+/-)', str(total_updated_month), str(round(Decimal(100*float(total_updated_month))/total_closed_month,1)) ], \
['Verified+Updated', str(total_verified_month+total_updated_month), str(round(Decimal(100*(float(total_verified_month+total_updated_month))/total_closed_month,1)))], \
['Unverified', str(total_unverified_month), str(round(Decimal(100*float(total_unverified_month))/total_closed_month,1))], \
]
closed_rfis_df = pd.DataFrame(data, columns=['Closed RFIs',str(total_closed_month), '  %  '])
closed_rfis_df.to_html(save_data_dir + 'closed_rfis.html', index=False)


####### RFI Response Metrics  #############
min_response_time = 5
max_response_time = 3500
rfi_response_df = rfistats_df[['country','event_id','create_date','iso_create_date','action_date','first_response_date','status','outcome']]

#### RFI - month
month_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(start_year, month, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
rfi_df = rfi_response_df.loc[month_mask]
#print(rfi_df)

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]
rfi_closed = len(rfi_closed_df)

# Responses, time, and rate
rfi_df['answered'] = np.where(rfi_df['first_response_date'].notnull(), 1, 0)
rfi_df['reaction_time'] = (rfi_df['first_response_date'] - rfi_df['iso_create_date']).astype('timedelta64[m]')
lt24hr_response = len(rfi_df[rfi_df.reaction_time < 1440])

# Response time
rfi_minmax_df = rfi_df[(rfi_df.reaction_time >  min_response_time) & (rfi_df.reaction_time <  max_response_time) ]
response_time_month = rfi_minmax_df.reaction_time.sum()

responses_month = rfi_df.answered.sum()
response_rate_month = 100*responses_month/rfi_closed
avg_response_rate_month = int(round(response_time_month/responses_month))
avg_response_rate_hm_month = str(timedelta(minutes=avg_response_rate_month))[:-3]
lt24hr_response_percent_month = int(round(100*lt24hr_response/responses_month))
rfi_closed_month = rfi_closed

#### RFI - year to date
ytd_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(start_year, 1, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, next_month+1, 1)) )
rfi_df = rfi_response_df.loc[ytd_mask]

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]
rfi_closed = len(rfi_closed_df)

# Responses, time, and rate
rfi_df['answered'] = np.where(rfi_df['first_response_date'].notnull(), 1, 0)
rfi_df['reaction_time'] = (rfi_df['first_response_date'] - rfi_df['iso_create_date']).astype('timedelta64[m]')
lt24hr_response = len(rfi_df[rfi_df.reaction_time < 1440])


# Response time
rfi_minmax_df = rfi_df[(rfi_df.reaction_time >  min_response_time) & (rfi_df.reaction_time <  max_response_time) ]
response_time_ytd = rfi_minmax_df.reaction_time.sum()

responses_ytd = rfi_df.answered.sum()
response_rate_ytd = 100*responses_ytd/rfi_closed
avg_response_rate_ytd = int(round(response_time_ytd/responses_ytd))
avg_response_rate_hm_ytd = str(timedelta(minutes=avg_response_rate_ytd))[:-3]
lt24hr_response_percent_ytd = int(round(100*lt24hr_response/responses_ytd))
rfi_closed_ytd = rfi_closed

#### RFI - last year
lyear_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(year-1, 1, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, 1, 1)) )
rfi_df = rfi_response_df.loc[lyear_mask]
#print(rfi_df)

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]
rfi_closed = len(rfi_closed_df)

# Responses, time, and rate
rfi_df['answered'] = np.where(rfi_df['first_response_date'].notnull(), 1, 0)
rfi_df['reaction_time'] = (rfi_df['first_response_date'] - rfi_df['iso_create_date']).astype('timedelta64[m]')
lt24hr_response = len(rfi_df[rfi_df.reaction_time < 1440])

# Response time
rfi_minmax_df = rfi_df[(rfi_df.reaction_time >  min_response_time) & (rfi_df.reaction_time <  max_response_time) ]
response_time_lyear = rfi_minmax_df.reaction_time.sum()

responses_lyear = rfi_df.answered.sum()
response_rate_lyear = 100*responses_lyear/rfi_closed
avg_response_rate_lyear = int(round(response_time_lyear/responses_lyear))
avg_response_rate_hm_lyear = str(timedelta(minutes=avg_response_rate_lyear))[:-3]
lt24hr_response_percent_lyear = int(round(100*lt24hr_response/responses_lyear))
rfi_closed_lyear = rfi_closed

#### Print RFI Response Metrics Table
# Month
#print(months[month-1] + " " + str(year))
#print('closed rfis: ' + str(rfi_closed_month))
#print('repsonses: ' + str(responses_month))
#print('response rate: ' + str(response_rate_month) + '%')
#print('average response rate (h:mm) : ' + avg_response_rate_hm_month)
#print('RFI answered < 24 hrs/RFIs answered (%): ' + str(lt24hr_response_percent_month))

# Year to date
#print("Overall - " + str(year))
#print('closed rfis: ' + str(rfi_closed_ytd))
#print('repsonses: ' + str(responses_ytd))
#print('response rate: ' + str(response_rate_ytd) + '%')
#print('average response rate (h:mm) : ' + avg_response_rate_hm_ytd)
#print('RFI answered < 24 hrs/RFIs answered (%): ' + str(lt24hr_response_percent_ytd))

# Last year
#print("Overall - " + str(year-1))
#print('closed rfis: ' + str(rfi_closed_lyear))
#print('repsonses: ' + str(responses_lyear))
#print('response rate: ' + str(response_rate_lyear) + '%')
#print('average response rate (h:mm) : ' + avg_response_rate_hm_lyear)
#print('RFI answered < 24 hrs/RFIs answered (%): ' + str(lt24hr_response_percent_lyear))

# RFI Response Metrics Data frame for html
rfi_metrics_data = [[months[month-1] + " " + str(year), str(rfi_closed_month),  str(responses_month), str(response_rate_month) + "%", avg_response_rate_hm_month, str(lt24hr_response_percent_month) + "%"], \
[str(year), str(rfi_closed_ytd),  str(responses_ytd), str(response_rate_ytd) + "%", avg_response_rate_hm_ytd, str(lt24hr_response_percent_ytd) + "%"], \
[str(year-1), str(rfi_closed_lyear),  str(responses_lyear), str(response_rate_lyear) + "%", avg_response_rate_hm_lyear, str(lt24hr_response_percent_lyear) + "%"] ]
rfi_response_metrics_df = pd.DataFrame(rfi_metrics_data, columns=['Response Metrics','Closed RFIs', 'RFIs Responded', 'Response rate', 'Average Response Time (h:min)**', 'RFIs answered <24hrs/RFIs answered'])
rfi_response_metrics_df.to_html(save_data_dir + 'rfi_response_metrics.html', index=False)


######## Verification rates per country


#### Unverified RFIs - last month
#ytd_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(start_year, 1, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, next_month+1, 1)) )
month_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(start_year, month, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
rfi_df = rfi_response_df.loc[month_mask]

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]

unverified_df = rfi_closed_df[rfi_df.outcome == 'Unverified']
rfi_country_unverified_df = unverified_df.groupby(['country']).outcome.count().reset_index(name='unverified').sort_values(['country'])
rfi_country_unverified_df.sort_values(['unverified'], ascending=False, inplace=True)
rfi_country_unverified_df=rfi_country_unverified_df.rename(columns = {'country':'Country'})
#print(rfi_country_unverified_df)
rfi_country_unverified_df.to_html(save_data_dir + 'rfi_country_unverified.html', index=False)


#### Lowest verification rates - Last year
tyear_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(year-1, 1, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, 1, 1)) )
rfi_df = rfi_response_df.loc[tyear_mask]

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]

#strip leading and trail spaces in country name
rfi_closed_df['country'] = rfi_closed_df['country'].str.strip()
# fix repeat countries
rfi_closed_df = rfi_closed_df.replace({'United States':'USA'})

# get total rfi count for each country
rfi_country = rfi_closed_df.groupby(['country']).size().reset_index(name='rfi_count')
#print(rfi_country)

# get verified rfi count for each country
verified_df = rfi_closed_df[(rfi_df.outcome == "Verified (+)") | (rfi_df.outcome == "Verified (-)")]
rfi_country_verified = verified_df.groupby(['country']).outcome.count().reset_index(name='verified').sort_values(['country'])
#print(rfi_country_verified)

# merge total rfi and verified rfi dataframes
rfi_ver_country = rfi_country.merge(rfi_country_verified, how='left', on='country')
rfi_ver_country.fillna(0, inplace=True)
rfi_ver_country['verified'] = rfi_ver_country['verified'].astype(int)
#print(rfi_ver_country)

# calculate verification rate
rfi_ver_country['ver_rate'] = (100*rfi_ver_country['verified']/rfi_ver_country['rfi_count']).round(1)

# save lowest verification rate and sort
rfi_ver_country_min = rfi_ver_country[(rfi_ver_country.ver_rate < 40) & (rfi_ver_country.rfi_count > 4)]
rfi_ver_country_min.sort_values(['ver_rate'], inplace=True)

rfi_ver_country_min=rfi_ver_country_min.rename(columns = {'country':'Country', 'rfi_count':'# RFIs', 'ver_rate': 'Verification Rate (%)'})
#print(rfi_ver_country_min)
rfi_ver_country_min.to_html(save_data_dir + 'rfi_ver_country.html', index=False)



#### Lowest verification rates - Year to date
ytd_mask = (rfi_response_df['create_date'] > pd.Timestamp(datetime.date(start_year, 1, 1)) ) & (rfi_response_df['create_date'] < pd.Timestamp(datetime.date(year, next_month+1, 1)) )
rfi_df = rfi_response_df.loc[ytd_mask]

# Closed RFIs
close_mask = rfi_df['status'] == 'C'
rfi_closed_df = rfi_df.loc[close_mask]

#strip leading and trail spaces in country name
rfi_closed_df['country'] = rfi_closed_df['country'].str.strip()
# fix repeat countries
rfi_closed_df = rfi_closed_df.replace({'United States':'USA'})

# get total rfi count for each country
rfi_country = rfi_closed_df.groupby(['country']).size().reset_index(name='rfi_count')
#print(rfi_country)

# get verified rfi count for each country
verified_df = rfi_closed_df[(rfi_df.outcome == "Verified (+)") | (rfi_df.outcome == "Verified (-)")]
rfi_country_verified = verified_df.groupby(['country']).outcome.count().reset_index(name='verified').sort_values(['country'])
#print(rfi_country_verified)

# merge total rfi and verified rfi dataframes
rfi_ver_country = rfi_country.merge(rfi_country_verified, how='left', on='country')
rfi_ver_country.fillna(0, inplace=True)
rfi_ver_country['verified'] = rfi_ver_country['verified'].astype(int)
#print(rfi_ver_country)

# calculate verification rate
rfi_ver_country['ver_rate'] = (100*rfi_ver_country['verified']/rfi_ver_country['rfi_count']).round(1)

# save lowest verification rate and sort
rfi_ver_country_min = rfi_ver_country[(rfi_ver_country.ver_rate < 40) & (rfi_ver_country.rfi_count > 4)]
rfi_ver_country_min.sort_values(['ver_rate'], inplace=True)

rfi_ver_country_min=rfi_ver_country_min.rename(columns = {'country':'Country', 'rfi_count':'# RFIs', 'ver_rate': 'Verification Rate (%)'})
#print(rfi_ver_country_min)
rfi_ver_country_min.to_html(save_data_dir + 'rfi_ver_country_ytd.html', index=False)

