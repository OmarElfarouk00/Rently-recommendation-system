from flask import Flask, request, jsonify
import pandas as pd
from sqlalchemy import create_engine

app = Flask(__name__)

# Database configuration
DB_URI = "mysql+pymysql://root:@localhost/propertymanagementsystem1"
engine = create_engine(DB_URI)

# Load data globally
def load_data():
    # Interactions
    rentals = pd.read_sql("SELECT id_client, id_property FROM Rental", engine)
    favorites = pd.read_sql("SELECT id_client, id_property FROM Favorits", engine)
    interactions = pd.concat([rentals, favorites], ignore_index=True).drop_duplicates()
    interactions["interaction"] = 1
    interaction_matrix = interactions.pivot_table(
        index="id_client", columns="id_property", values="interaction", fill_value=0
    )

    # Load properties + owner
    properties = pd.read_sql("""
        SELECT p.id_property, p.propertyType, p.city, p.bedrooms, p.bathrooms, 
               p.size, p.ownerNeeds, po.id_propertyOwner
        FROM Property p
        JOIN PropertyOwner po ON p.id_propertyOwner = po.id_propertyOwner
    """, engine)

    # Load VIP owners
    vip_owners = pd.read_sql("""
        SELECT id_propertyOwner FROM PropertyOwner_VIP 
        WHERE VipEntDate > CURDATE()
    """, engine)
    vip_owner_ids = set(vip_owners['id_propertyOwner'])

    # Mark VIP dynamically
    properties["is_vip"] = properties["id_propertyOwner"].apply(lambda x: 1 if x in vip_owner_ids else 0)

    return interaction_matrix, properties

interaction_matrix, properties_df = load_data()

# Custom similarity score function
def custom_similarity_score(target_property, candidate_property):
    score = 0.0

    # Match property type
    if target_property['propertyType'] == candidate_property['propertyType']:
        score += 1.0

        # Match number of bedrooms if apartment
        if target_property['propertyType'] == 'apartment' and \
           target_property['bedrooms'] == candidate_property['bedrooms']:
            score += 0.5

    # Match city
    if target_property['city'].strip().lower() == candidate_property['city'].strip().lower():
        score += 1.0

    # Match bathrooms
    if target_property['bathrooms'] == candidate_property['bathrooms']:
        score += 0.2

    # Size within ±10%
    try:
        if abs(target_property['size'] - candidate_property['size']) / max(1, target_property['size']) <= 0.1:
            score += 0.3
    except:
        pass


    # VIP boost
    if candidate_property['is_vip'] == 1:
        score += 0.3

    return score

# Recommendation function
def recommend_custom(user_id, top_n=5):
    if user_id not in interaction_matrix.index:
        return []

    user_interacted_ids = interaction_matrix.loc[user_id][interaction_matrix.loc[user_id] > 0].index.tolist()

    interacted_props = properties_df[properties_df['id_property'].isin(user_interacted_ids)]
    candidate_props = properties_df[~properties_df['id_property'].isin(user_interacted_ids)]

    scores = {}

    for _, candidate in candidate_props.iterrows():
        total_score = 0.0
        for _, interacted in interacted_props.iterrows():
            total_score += custom_similarity_score(interacted, candidate)
        scores[candidate['id_property']] = total_score

    # Sort scores
    sorted_scores = sorted(scores.items(), key=lambda x: x[1], reverse=True)

    # ✅ Print all scores
    print("\n=== Recommendation Scores ===")
    for prop_id, score in sorted_scores:
        print(f"Property ID {prop_id}: Score = {score}")
    print("=== End of Scores ===\n")

    return [prop_id for prop_id, score in sorted_scores[:top_n]]


# Flask API route
@app.route('/recommend', methods=['GET'])
def recommend_endpoint():
    try:
        user_id = int(request.args.get('user_id'))
        top_n = int(request.args.get('top_n', 5))
    except (TypeError, ValueError):
        return jsonify({"error": "Invalid or missing parameters"}), 400

    recommended_ids = recommend_custom(user_id, top_n)
    return jsonify({"recommended_property_ids": recommended_ids})

if __name__ == '__main__':
    app.run(debug=True, port=5050)
