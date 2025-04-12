import requests
import pandas as pd
from sklearn.preprocessing import LabelEncoder
from sklearn.svm import SVR
from geopy.distance import geodesic
import numpy as np

# Initialize an empty DataFrame with the required columns
teachers = pd.DataFrame(columns=['id', 'education', 'subject', 'location'])

# URL of the PHP script
url_T = "http://localhost/Matching/matching_T.php"  # Adjust the URL as needed

# Make the GET request
response = requests.get(url_T)

# Check if the request was successful
if response.status_code == 200:
    try:
        # Parse the JSON response
        json_data = response.json()

        # Initialize a dictionary to separate data by `app_creator`
        separated_data = {}

        # Iterate through the JSON data and group by `app_creator`
        for item in json_data:
            creator = item.get('app_creator')
            if creator not in separated_data:
                separated_data[creator] = []  # Initialize a list for the creator
            separated_data[creator].append(item)  # Add the current item to the group

        # Convert the data into a Pandas DataFrame
        for creator, items in separated_data.items():
            for data in items:
                # Convert latitude and longitude to strings and concatenate them
                location = f"{data.get('Latitude')}, {data.get('Longitude')}"

                # Create a DataFrame for the current row
                row = pd.DataFrame([{
                    'id': data.get('member_id'),
                    'education': data.get('class_level_name'),  # Replace `class_name` with the actual column name
                    'subject': data.get('subject_name'),
                    'location': location
                }])
                # Concatenate the new row to the main DataFrame
                teachers = pd.concat([teachers, row], ignore_index=True)

    except ValueError as e:
        print("Error parsing JSON:", e)
else:
    print("Failed to retrieve data. Status code:", response.status_code)


students = pd.DataFrame(columns=['id', 'education', 'learning_need', 'location'])

# URL of the PHP script
url_PS = "http://localhost/Matching/matching_PS.php"  # Adjust the URL as needed

# Make the GET request
response = requests.get(url_PS)

# Check if the request was successful
if response.status_code == 200:
    try:
        # Parse the JSON response
        json_data = response.json()

        # Initialize a dictionary to separate data by `app_creator`
        separated_data = {}

        # Iterate through the JSON data and group by `app_creator`
        for item in json_data:
            creator = item.get('app_creator')
            if creator not in separated_data:
                separated_data[creator] = []  # Initialize a list for the creator
            separated_data[creator].append(item)  # Add the current item to the group

        # Convert the data into a Pandas DataFrame
        for creator, items in separated_data.items():
            for data in items:
                # Convert latitude and longitude to strings and concatenate them
                location = f"{data.get('Latitude')}, {data.get('Longitude')}"

                # Create a DataFrame for the current row
                row = pd.DataFrame([{
                    'id': data.get('member_id'),
                    'education': data.get('class_level_name'),  # Replace `class_name` with the actual column name
                    'learning_need': data.get('subject_name'),
                    'location': location
                }])
                # Concatenate the new row to the main DataFrame
                students = pd.concat([students, row], ignore_index=True)

    except ValueError as e:
        print("Error parsing JSON:", e)
else:
    print("Failed to retrieve data. Status code:", response.status_code)

print(teachers)
print(students)

# Ensure all possible categories are included
def split_location(location):
    """Split a location string 'lat, lon' into latitude and longitude."""
    lat, lon = map(float, location.split(', '))
    return lat, lon

# Process teachers data
teachers[['latitude', 'longitude']] = teachers['location'].apply(lambda loc: pd.Series(split_location(loc)))

# Process students data
students[['latitude', 'longitude']] = students['location'].apply(lambda loc: pd.Series(split_location(loc)))

# Combine all possible education levels and subjects
all_education_levels = list(teachers['education']) + list(students['education'])
all_subjects = list(teachers['subject']) + list(students['learning_need'])

# Encode categorical data
le_education = LabelEncoder()
le_subject = LabelEncoder()

# Fit encoders with all combined categories
le_education.fit(all_education_levels)
le_subject.fit(all_subjects)

# Transform teacher and student data
teachers['education'] = le_education.transform(teachers['education'])
teachers['subject'] = le_subject.transform(teachers['subject'])
students['education'] = le_education.transform(students['education'])
students['learning_need'] = le_subject.transform(students['learning_need'])

# Create feature matrices for teachers and students
teacher_features = np.hstack((
    teachers[['education', 'subject']].values, 
    teachers[['latitude', 'longitude']].values
))
student_features = np.hstack((
    students[['education', 'learning_need']].values, 
    students[['latitude', 'longitude']].values
))

# Train an SVR model
svr = SVR(kernel='linear')
svr.fit(teacher_features, teachers['latitude'])  # Use latitude as the target

# Matching score calculation function
def calculate_distance(location1, location2):
    """Calculate the geographic distance between two lat/lon pairs."""
    lat1, lon1 = map(float, location1.split(', '))
    lat2, lon2 = map(float, location2.split(', '))
    return geodesic((lat1, lon1), (lat2, lon2)).kilometers

def calculate_matching_score(geo_distance, feature_distance, education_match, subject_match):
    """Calculate the matching score with finer adjustments based on different distance ranges."""
    
    # Define different geographic distance ranges and their impact on the matching score
    if geo_distance <= 5:  # Within 5 km, less penalty
        geo_score = 1 - geo_distance / 10  # Smaller reduction for closer distances
    elif geo_distance <= 10:  # Between 5 and 10 km, moderate penalty
        geo_score = 1 - geo_distance / 15  # Moderate reduction
    else:  # Greater than 10 km, higher penalty
        geo_score = 1 - geo_distance / 20  # Larger reduction for distant locations
    
    # Ensure geo_score does not go below 0 (i.e., the minimum score is 0)
    geo_score = max(geo_score, 0)
    
    # Combine the other factors (education and subject match)
    return (0.4 * geo_score + 0.2 * education_match + 0.4 * subject_match) * 100

# Compute matching scores
match_scores = []
for i, student in students.iterrows():
    for j, teacher in teachers.iterrows():
        # Compute distances
        geo_distance = calculate_distance(student['location'], teacher['location'])
        # Predict feature distance
        feature_distance = svr.predict([student_features[i]])[0]
        # Check for matches
        education_match = 1 if student['education'] == teacher['education'] else 0
        subject_match = 1 if student['learning_need'] == teacher['subject'] else 0
        # Compute total score
        total_score = calculate_matching_score(geo_distance, feature_distance, education_match, subject_match)
        match_scores.append((teacher['id'], student['id'], f"{total_score:.2f}%"))

# Convert matches into a DataFrame and filter for matches > 50%
match_scores_df = pd.DataFrame(match_scores, columns=['teacher_id', 'student_id', 'score'])
high_matches = match_scores_df[match_scores_df['score'].apply(lambda x: float(x[:-1])) > 50]

#convert the matching result to json
high_matches_json = high_matches.to_json(orient="records", lines=True)

with open("high_matches.json", "w") as json_file:
    json_file.write(high_matches_json)

# Output matches
#print(high_matches)

feature_distance = svr.predict([student_features[i]])[0]
print(f"预测的特征距离: {feature_distance}")

geo_distance = calculate_distance(student['location'], teacher['location'])
print(f"地理距离: {geo_distance} km")
