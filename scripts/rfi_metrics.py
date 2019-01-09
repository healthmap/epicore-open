# Jeff Andre
# December, 26 2018
#
# Generates tables for Responder RFI metrics, RFI metrics dashboard, and Requester metrics dashboard.
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
import pandas as pd
import numpy as np
import itertools
from pandas.plotting import table
import sys
import os

reload(sys)
sys.setdefaultencoding('utf-8')

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
#print( datetime.date(year, month, 1))
#print(datetime.date(year, next_month, 1))
mask = (rfistats_df['create_date'] > pd.Timestamp(datetime.date(year, month, 1)) ) & (rfistats_df['create_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
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
data = [['EpiCore', str(total_rfi_epicore), str(int(round(total_rfi_epicore/100*total_rfi_month))) ], \
['HealthMap', str(total_rfi_healthmap), str(int(round(100*total_rfi_healthmap/total_rfi_month))) ], \
['MSF Spain (OCBA)', str(total_rfi_msf), str(int(round(100*total_rfi_msf/total_rfi_month))) ], \
['ProMED', str(total_rfi_promed), str(int(round(100*total_rfi_promed/total_rfi_month)))] ]

opened_rfis_df = pd.DataFrame(data, columns=['Opened RFIs', str(total_rfi_month), '  %  '])
opened_rfis_df.to_html(save_data_dir + 'opened_rfis.html', index=False)

# create data frame for closed RFIs for the month
data = [['Verified (+/-)', str(total_verified_month), str(int(round(100*total_verified_month/total_closed_month))) ], \
['Updated (+/-)', str(total_updated_month), str(int(round(100*total_updated_month/total_closed_month))) ], \
['Unverified', str(total_unverified_month), str(int(round(100*total_unverified_month/total_closed_month)))] ]
closed_rfis_df = pd.DataFrame(data, columns=['Closed RFIs',str(total_closed_month), '  %  '])
closed_rfis_df.to_html(save_data_dir + 'closed_rfis.html', index=False)

########### Dataframe for RFI dashboard #######################
# need to add closure date, answered, and reaction time columns
# reaction time = first response datetime - rfi datetime
# answered = first response datetime not null
### not used now
#epicore_url = 'https://epicore.org/#/events2/'
#rfi_df = rfistats_df[['event_id','title','create_date','iso_create_date','action_date','first_response_date', 'organization_id','person','status','outcome']]
#rfi_df['answered'] = np.where(rfi_df['first_response_date'].notnull(), 'yes', 'no')
#rfi_df['reaction_time'] = rfi_df['first_response_date'] - rfi_df['iso_create_date']
#rfi_df['reaction_time'].fillna("-", inplace = True)
#rfi_df['organization_id'].replace(organizations, inplace=True)
#rfi_df['Quick link'] = epicore_url + rfi_df['event_id'].astype(str)
#rfi_dashboard_df = rfi_df[['event_id','title','create_date','organization_id','person','Quick link','status','outcome', \
#                           'action_date', 'answered', 'reaction_time']]
#rfi_dashboard_df['create_date'] = rfi_dashboard_df['create_date'].dt.strftime('%d-%m-%Y')
#rfi_dashboard_df.sort_values(by='create_date', inplace=True)
#rfi_dashboard_df.rename({'title': 'Title','person': 'Requester','action_date':'Closure Date', 'organization_id': 'Organization', \
#                         'create_date': 'RFI Date', 'event_id': 'RFI'}, axis='columns', inplace=True)

#rfi_dashboard_df.to_html(save_data_dir + 'rfi_dashboard.html', index=False)