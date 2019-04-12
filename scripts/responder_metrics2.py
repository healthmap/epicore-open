# July 27, 2018
# Jeff Andre
#
# Generates EpiCore Responders Metrics 
#
# usage:
#
#  - no args: generates report for last month of data
#    resonder_metrics.py
#
#  - with args: generates report for month and year
#    responder_metrics.py month year
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

# set max width
pd.set_option('display.max_colwidth', -1)

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

# new applicants and by region
def df_table_image(df, file_name, title):
    ax = plt.subplot(111, frame_on=False) # no visible frame
    ax.xaxis.set_visible(False)  # hide the x axis
    ax.yaxis.set_visible(False)  # hide the y axis
    df_table = table(ax,df,rowLabels=['']*df.shape[0], loc='center', cellLoc='left', colWidths = [0.75, 0.5])
    df_table.scale(1,1.5)
    plt.savefig(file_name, bbox_inches='tight')
    plt.close()

# no members countries
def df_table_image1(df, file_name, title):
    ax = plt.subplot(111, frame_on=False) # no visible frame
    ax.xaxis.set_visible(False)  # hide the x axis
    ax.yaxis.set_visible(False)  # hide the y axis
    #plt.title(title)
    df_table = table(ax,df,rowLabels=['']*df.shape[0], loc='center', cellLoc='left', colWidths = [0.75, 0.25])
    df_table.scale(1,1.5)
    plt.savefig(file_name, bbox_inches='tight')
    plt.close()

# applicants by country
def df_table_image2(df, file_name, title):
    ax = plt.subplot(111, frame_on=False) # no visible frame
    ax.xaxis.set_visible(False)  # hide the x axis
    ax.yaxis.set_visible(False)  # hide the y axis
    #plt.title(title)
    df_table = table(ax,df,rowLabels=['']*df.shape[0], loc='center', cellLoc='left', colWidths = [0.7, 0.2,0.3])
    df_table.scale(1,1.5)
    plt.savefig(file_name, bbox_inches='tight')
    plt.close()

# experience
def df_table_image3(df, file_name, title):
    ax = plt.subplot(111, frame_on=False) # no visible frame
    ax.xaxis.set_visible(False)  # hide the x axis
    ax.yaxis.set_visible(False)  # hide the y axis
    #plt.title(title)
    df_table = table(ax,df,rowLabels=['']*df.shape[0], loc='center', cellLoc='left', colWidths = [0.8, 0.25, 0.25])
    df_table.scale(1,1.5)
    plt.savefig(file_name, bbox_inches='tight')
    plt.close()


data_dir = '/var/www/html/prod.epicore.org/data/'
image_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'img/metrics')) +'/';
save_data_dir = os.path.abspath(os.path.join(os.path.dirname( __file__ ), '..', 'data')) +'/';

# read world population data
population_df = pd.read_csv(data_dir + 'population_2018.csv', encoding = "ISO-8859-1")
population_df['country_code'].fillna('NA', inplace=True) # fix for Namibia (country code NA)

# read un country codes
un_country_codes_df = pd.read_csv(data_dir + 'un_countrycode.csv', encoding = "ISO-8859-1")

# read un country codes
un_country_codes_region_df = pd.read_csv(data_dir + 'un_countrycode_region.csv', encoding = "ISO-8859-1")

# read member data into dataframe
member_df = pd.read_csv(data_dir + 'approval.csv')
# clean up data column names
member_df.columns = member_df.columns.to_series().str.strip().str.lower().str.replace(' ', '_').str.replace('(', '').str.replace(')', '')
# format dates for sorting
member_df['application_date'] = pd.to_datetime(member_df['application_date'])
member_df['approval_date'] = pd.to_datetime(member_df['approval_date'])
member_df['acceptance_date'] = pd.to_datetime(member_df['acceptance_date'])
# convert ids to int, set id NA values to 0
#member_df = member_df[member_df.member_id.notnull()]
member_df['member_id'].fillna('0', inplace=True)
member_df['member_id'] = member_df.member_id.astype(int)
member_df['country_code'].fillna('NA', inplace=True) # fix for Namibia (country code NA)

# group and sort applicants and approved members by date
app_df = member_df.groupby(['application_date']).application_date.count().reset_index(name='applicants').sort_values(['application_date'])
# group and sort by approval_date
approved_df = member_df.groupby(['approval_date']).approval_date.count().reset_index(name='approved').sort_values(['approval_date'])

# group approved members by health experience
experience_df = member_df.groupby(['health_experience','approval_date']).health_experience.count().reset_index(name='count')
members_approved_df = member_df[member_df.approval_date.notnull()]
#exp_df = member_df.groupby(['health_experience']).health_experience.count().reset_index(name='count')
exp_df = members_approved_df.groupby(['health_experience']).health_experience.count().reset_index(name='count')

# total experience
total_exp = exp_df['count'].sum()
# add column for percent values
exp_df['percent'] = exp_df['count']*100/total_exp
exp_df.percent = exp_df.percent.round().astype(int)
exp_df.rename({'count': 'n','percent':'%'}, axis='columns', inplace=True)
# create image for report
exp_df.to_html(save_data_dir + 'experience_table.html', index=False)
#df_table_image3(exp_df, image_dir + 'experience_table.png', 'Professional Background')


# get total human experience, filtered by "human"
human_exp_df = exp_df[exp_df['health_experience'].str.lower().str.contains("human")]
total_human_exp = human_exp_df['%'].sum()
#human_exp_df = experience_df[experience_df['health_experience'].str.lower().str.contains("human")]
#total_human_exp = human_exp_df['n'].sum()

# get total animal experience, filtered by "animal:
animal_exp_df = exp_df[exp_df['health_experience'].str.lower().str.contains("animal")]
total_animal_exp = animal_exp_df['%'].sum()
#animal_exp_df = experience_df[experience_df['health_experience'].str.lower().str.contains("animal")]
#total_animal_exp = animal_exp_df['n'].sum()

# get total environmental experience, filtered by "environmental"
environmental_exp_df = exp_df[exp_df['health_experience'].str.lower().str.contains("environmental")]
total_environmental_exp = environmental_exp_df['%'].sum()
#environmental_exp_df = experience_df[experience_df['health_experience'].str.lower().str.contains("environmental")]
#total_environmental_exp = environmental_exp_df['n'].sum()

# plot expertise bar chart
total_health_exp = total_human_exp + total_animal_exp + total_environmental_exp
human_exp = round((100*total_human_exp/total_health_exp))
animal_exp = round((100*total_animal_exp/total_health_exp))
env_exp = round((100*total_environmental_exp/total_health_exp))
fig2 = plt.figure()
x = np.arange(3)
y = [total_human_exp, total_animal_exp, total_environmental_exp]
#y = [human_exp, animal_exp, env_exp]
#plt.title('Overall Health Expertise (%)')
hm,an,en = plt.bar(x, y, width=0.4, align="center")
hm.set_facecolor('#015163')
an.set_facecolor('#0089aa')
en.set_facecolor('#00bbe8')
plt.xticks(x,('Human','Animal','Environmental'))
#add value labels to bars
for a,b in zip(x, y):
    plt.text(a, b+0.5, str(b)+'%')
fig2.savefig(image_dir + "health_expertise.png", bbox_inches='tight')

# generate member applications plot
fig1 = plt.figure()
this_year = datetime.date.today().year
member_title = 'Epicore Applicants ' + str(last_year) + '-' + str(this_year)
#plt.title(member_title)
plt.plot(app_df.application_date, app_df.applicants)
plt.xticks(rotation=40)
plt.xlim([datetime.date(last_year, 1, 1), datetime.datetime.now()])
plt.ylim([0,10])
fig1.savefig(image_dir + "applicants_year.png",  bbox_inches='tight')

# get applicants for another plot
plot_year = year
plot_start_month = month
if plot_start_month == 12:
    plot_year = year-1
mask = (app_df['application_date'] > pd.Timestamp(datetime.date(plot_year, plot_start_month, 1)) ) & (app_df['application_date'] < pd.Timestamp(datetime.datetime.now()) )
applicants_plot_df = app_df.loc[mask]
# generate plot
fig1 = plt.figure()
plt.plot(applicants_plot_df.application_date, applicants_plot_df.applicants)
plt.xticks(rotation=40)
plt.xlim([datetime.date(plot_year, plot_start_month, 1), datetime.datetime.now()])
plt.ylim([0,10])
fig1.savefig(image_dir + "applicants_month.png",  bbox_inches='tight')

# get total applicants for the month
start_year = year
if next_month == 1:
    start_year = year-1
mask = (app_df['application_date'] >= pd.Timestamp(datetime.date(start_year, month, 1)) ) & (app_df['application_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
applicants_month = app_df.loc[mask]
total_applicants = applicants_month['applicants'].sum()

# get accepted applicants with no training
today = datetime.datetime.now()
two_months_ago = today - datetime.timedelta(days=60)
#mask = (member_df['user_status'] == 'Accepted') & member_df['course_type'].isnull() & (member_df['acceptance_date'] > two_months_ago)
mask = (member_df['user_status'] == 'Accepted') & member_df['course_type'].isnull() & (member_df['acceptance_date'] > pd.Timestamp(datetime.date(2018, 1, 1)))
app_no_training_df = member_df.loc[mask]
app_no_training_df = app_no_training_df[['acceptance_date','member_id']]
app_no_training_df.to_html(save_data_dir + 'app_no_training_table.html', index=False)

# get heard about applicants
start_year = year
if next_month == 1:
    start_year = year-1
mask = (member_df['application_date'] > pd.Timestamp(datetime.date(start_year, month, 1)) ) & (member_df['application_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
members_month_df = member_df.loc[mask]
members_month_df.rename(columns={'heard_about_epicore_by':'heard_about'}, inplace=True)
heard_about_df = members_month_df.groupby(['heard_about']).heard_about.count().reset_index(name='applicants').sort_values(['heard_about'])
# remove problem decodes chars for to_html
heard_about_df['heard_about'] = heard_about_df.heard_about.str.decode('ascii', errors='ignore')
heard_about_df.to_html(save_data_dir + 'heard_about_table.html', index=False)

# get total approved members for the month
mask = (approved_df['approval_date'] >= pd.Timestamp(datetime.date(start_year, month, 1)) ) & (approved_df['approval_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
approved_month = approved_df.loc[mask]
total_approved_month = approved_month['approved'].sum()
total_approved = approved_df['approved'].sum()

# group and sort by application_date, country
app_country_date_df = member_df.groupby(['country','application_date']).country.count().reset_index(name='applicants').sort_values(['country'])

# get new applicants for each country by month
mask = (app_country_date_df['application_date'] > pd.Timestamp(datetime.date(start_year, month, 1)) ) & (app_country_date_df['application_date'] < pd.Timestamp(datetime.date(year, next_month, 1)) )
app_country_month = app_country_date_df.loc[mask]
app_by_country = app_country_month.groupby(['country']).country.count().reset_index(name='new applicants')
# create image for report
app_by_country.to_html(save_data_dir + 'new_applicants_table.html', index=False)
#df_table_image(app_by_country, image_dir + 'new_applicants_table.png', '')

# group and sort by country
approved_mask = member_df['user_status'] == 'Approved'
member_approved_df = member_df.loc[approved_mask]
app_country_df = member_approved_df.groupby(['country','country_code','who_region']).country.count().reset_index(name='applicants').sort_values(['country'])
# merge country and population tables
app_country_pop_df = app_country_df.merge(population_df, how='left', on='country_code')
app_country_pop_df.rename(columns={'country_x':'country'}, inplace=True)
del app_country_pop_df['country_y']

# merge with 3 letter UN country codes
app_country_pop_un_df = app_country_pop_df.merge(un_country_codes_df, how='left', on='country_code')
app_country_pop_un_df.rename(columns={'country_x':'country'}, inplace=True)
del app_country_pop_un_df['country_y']

# include member density (responders per 1 million population) and sort by density
app_country_pop_un_df['member_density'] = 1000*app_country_pop_un_df['applicants']/app_country_pop_un_df['population']
app_country_pop_un_df['member_density'] = app_country_pop_un_df['member_density'].round(2)
app_country_pop_un_df = app_country_pop_un_df.sort_values(['country'])

#get countries with no members
all_country_df = un_country_codes_df.merge(app_country_df, how='left', on='country_code')
all_country_df.rename(columns={'country_x':'country'}, inplace=True)
del all_country_df['country_y']
all_country_df.sort_values(['applicants'], inplace=True)
mask = all_country_df['applicants'].isnull()
no_member_countries = all_country_df.loc[mask]
no_member_countries = no_member_countries[['country','un_country_code']]
no_member_countries = no_member_countries[no_member_countries.un_country_code.notnull()]
total_no_member_countries = len(no_member_countries.index)
total_member_countries = len(app_country_df) #len(all_country_df.index) - total_no_member_countries
#df_table_image1(no_member_countries, image_dir + 'no_applicants_countries_table.png', '')
no_member_countries.to_html(save_data_dir + 'no_applicants_countries_table.html', index=False)

#merge no member countries with regions
no_member_countries_regions_df = no_member_countries.merge(un_country_codes_region_df, how='left', on='un_country_code')
no_member_countries_regions_df = no_member_countries_regions_df[['country_x','country_code','un_country_code','who_region']]
no_member_countries_regions_df.rename(columns={'country_x':'country', 'who_region':'Region'}, inplace=True)


# group by region
no_members_reg = no_member_countries_regions_df.groupby('Region').agg({'un_country_code': lambda x: ', '.join(x) } )

# applicants by country table
app_country_density_df = app_country_pop_un_df[['country','applicants','member_density']]
app_country_density_df.rename(columns={'applicants':'n'}, inplace=True)
app_country_density_df.to_html(save_data_dir + 'country_table.html', index=False)
#df_table_image2(app_country_density_df, image_dir + 'country_table.png', '')

# group by region, count members in regions, and sort by region
app_region_df = members_approved_df.groupby(['who_region']).who_region.count().reset_index(name='applicants').sort_values(['who_region'])

# re-arrange regions
central_america_df = app_region_df[app_region_df['who_region'].str.lower().str.contains("central america")]
central_america_total = central_america_df['applicants'].sum()
app_region_df.at[5,'who_region'] = 'Central-South America'
app_region_df.at[5,'applicants'] = int(central_america_total)
new_region_df = app_region_df.drop([3,7])
new_region_df.rename(columns={'who_region':'Region'}, inplace=True)

# create image
#df_table_image(new_region_df, image_dir + 'region_table.png', 'Applicants by Region')
new_region_df.to_html(save_data_dir + 'region_table.html', index=False)

# data frame for members by region merged with counties with no memebers
# calculate %
total_region_applicants = new_region_df.applicants.sum()
new_region_df['% members'] = (100*new_region_df['applicants']/total_region_applicants).round().astype(int)
#exp_df.percent = exp_df.percent.round().astype(int)
new_region_df.rename(columns={'applicants':'# members'}, inplace=True)


# merge with no members regions
no_members_region_df = new_region_df.merge(no_members_reg, how='left', on='Region')
no_members_region_df.rename(columns={'un_country_code':'Countries with no members (UN-3-letter codes)'}, inplace=True)
no_members_region_df.fillna('', inplace=True)

no_members_region_df.to_html(save_data_dir + 'no_members_region_table.html', index=False)


# creat data frame for memebership summary
data = [['Responders', total_approved], \
['New Applicants', str(total_applicants)], \
['New Responders', str(total_approved_month)], \
['Countries represented', str(total_member_countries) ], \
#['Countries not represented', str(total_no_member_countries)]
]

membership_df = pd.DataFrame(data, columns=['Metric','Value'])
membership_df.to_csv(save_data_dir + 'membership.csv', sep='|', index=False)
membership_df.to_html(save_data_dir + 'membership.html', index=False)
#df_table_image(membership_df, image_dir + 'membership.png', '')

# creat data frame for memebers in region
data_region = [ ['COUNTRIES included*: ' + str(total_member_countries), 'COUNTRIES missing**: ' +str(total_no_member_countries) ] ]

data_region_df = pd.DataFrame(data_region, columns=['', ''])
data_region_df.to_html(save_data_dir + 'members_regions.html', index=False)






