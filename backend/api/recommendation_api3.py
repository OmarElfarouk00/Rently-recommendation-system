from flask import Flask, request, jsonify
import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
from sqlalchemy import create_engine

app = Flask(__name__)
DB_URI = "mysql+pymysql://root:@localhost/propertymanagementsystem1"
engine = create_engine(DB_URI)

def load_data():
    rentals = pd.read_sql("SELECT id_client, id_property FROM Rental", engine)
    favorites = pd.read_sql("SELECT id_client, id_property FROM Favorits", engine)
    properties = pd.read_sql("SELECT id_property, propertyType, city FROM Property", engine)

    # Mark interaction types
    rentals["interaction"] = 2
    favorites["interaction"] = 1

    # Combine both with custom logic
    combined = pd.concat([rentals, favorites], ignore_index=True)

    # If both rental and favorite exist, total becomes 3
    data = combined.groupby(["id_client", "id_property"], as_index=False)["interaction"].sum()

    # Cap max interaction at 3 (in case of duplicates)
    data["interaction"] = data["interaction"].clip(upper=3)

    # Create interaction matrix
    interaction_matrix = data.pivot_table(
        index="id_client", columns="id_property", values="interaction", fill_value=0
    )

    # Prepare content features
    properties["city"] = properties["city"].str.strip().str.lower()
    properties["propertyType"] = properties["propertyType"].str.strip().str.lower()
    features = pd.get_dummies(properties[["propertyType", "city"]])
    content_features = features
    content_features.index = properties["id_property"]

    print("content features:", content_features)
    print("rentals", rentals)
    print("favorites", favorites)
    print("data", data)

    # Compute similarities
    content_sim = cosine_similarity(content_features)
    content_sim_df = pd.DataFrame(
        content_sim, index=content_features.index, columns=content_features.index
    )

    # user_similarity = cosine_similarity(interaction_matrix)
    if interaction_matrix.shape[0] == 0 or interaction_matrix.shape[1] == 0:
        user_similarity = pd.DataFrame()  # or np.zeros((0, 0))
    else:
        user_similarity = cosine_similarity(interaction_matrix)
        
    user_sim_df = pd.DataFrame(
        user_similarity, index=interaction_matrix.index, columns=interaction_matrix.index
    )

    print("content similarity:", content_sim_df)
    print("user similarity:", user_sim_df)
    print("interaction matrix:", interaction_matrix)

    return interaction_matrix, content_sim_df, user_sim_df




def recommend(user_id,interaction_matrix, content_sim_df, user_sim_df, top_n=5):
    if user_id not in interaction_matrix.index:
        return []

    user_vector = interaction_matrix.loc[user_id]
    interacted_items = user_vector[user_vector > 0].index

    # Keep interacted_items this time
    valid_items = [item for item in interacted_items if item in content_sim_df.columns]
    if not valid_items:
        return []

    # Get interaction values for valid items
    user_interactions = user_vector[valid_items]  # contains 1, 2, or 3

    # Compute weighted content similarity
    weighted_sim = content_sim_df[valid_items].dot(user_interactions)

    # Normalize by total weight (to get weighted average)
    weights_sum = user_interactions.sum()
    if weights_sum != 0:
        content_scores = weighted_sim / weights_sum
    else:
        content_scores = pd.Series(0, index=content_sim_df.index)


    # KEEP interacted items in scores
    similar_users = user_sim_df[user_id].sort_values(ascending=False)[1:6]
    similar_user_ids = similar_users.index
    similar_user_items = interaction_matrix.loc[similar_user_ids].mean(axis=0)

    # Also KEEP interacted items here
    # combined_scores = (content_scores + similar_user_items).dropna().sort_values(ascending=False)
    combined = content_scores.add(similar_user_items, fill_value=0)
    combined_scores = combined.sort_values(ascending=False)

    print("weighted sim:", weighted_sim)
    print("similar users:", similar_users)
    print("Interacted items:", interacted_items)
    print("Content scores:", content_scores.sort_values(ascending=False))
    print("Similar user items:", similar_user_items.sort_values(ascending=False))
    print("Combined scores:", combined_scores)
    print("interacted items:",interacted_items)
    return combined_scores.head(top_n).index.tolist()




@app.route('/recommend', methods=['GET'])
def recommend_endpoint():
    user_id = int(request.args.get('user_id'))
    top_n = int(request.args.get('top_n', 5))
    interaction_matrix, content_sim_df, user_sim_df = load_data()
    recommendations = recommend(user_id,interaction_matrix, content_sim_df, user_sim_df, top_n)
    return jsonify({'recommended_property_ids': recommendations})

if __name__ == '__main__':
    app.run(debug=False, port=5050, host='127.0.0.1')

