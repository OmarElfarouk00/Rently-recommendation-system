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

    combined = pd.concat([rentals, favorites], ignore_index=True)
    data = combined.groupby(["id_client", "id_property"], as_index=False)["interaction"].sum()
    data["interaction"] = data["interaction"].clip(upper=3)

    interaction_matrix = data.pivot_table(
        index="id_client", columns="id_property", values="interaction", fill_value=0
    )

    properties["city"] = properties["city"].str.strip().str.lower()
    properties["propertyType"] = properties["propertyType"].str.strip().str.lower()
    features = pd.get_dummies(properties[["propertyType", "city"]])
    content_features = features.set_index(properties["id_property"])

    content_sim = cosine_similarity(content_features)
    content_sim_df = pd.DataFrame(content_sim, index=content_features.index, columns=content_features.index)
    print("content",content_features)

    return interaction_matrix, content_sim_df, properties

# compute user-property similarity...
def compute_user_similarity(user_vector, interaction_matrix):
    if interaction_matrix.shape[0] == 0 or interaction_matrix.shape[1] == 0:
        return pd.Series(dtype=float)

    other_users = interaction_matrix.drop(user_vector.name)
    similarities = cosine_similarity(user_vector.values.reshape(1, -1), other_users.values)[0]
    user_sim_series = pd.Series(similarities, index=other_users.index)
    return user_sim_series.sort_values(ascending=False)

def recommend(user_id, interaction_matrix, content_sim_df, properties_df, top_n=5):
    if user_id not in interaction_matrix.index:
        return []

    user_vector = interaction_matrix.loc[user_id]
    interacted_items = user_vector[user_vector > 0].index

    valid_items = [item for item in interacted_items if item in content_sim_df.columns]
    if not valid_items:
        return []

# weighed content similarity
    user_interactions = user_vector[valid_items]
    weighted_sim = content_sim_df[valid_items].dot(user_interactions)
    weights_sum = user_interactions.sum()
    content_scores = weighted_sim / weights_sum if weights_sum else pd.Series(0, index=content_sim_df.index)

    user_sim = compute_user_similarity(user_vector, interaction_matrix)
    similar_user_ids = user_sim.head(5).index
    similar_user_items = interaction_matrix.loc[similar_user_ids].mean(axis=0)

    combined_scores = content_scores.add(similar_user_items, fill_value=0).sort_values(ascending=False)
    print("combined,", combined_scores)
    print("similar user items,", similar_user_items)
    print("similar user", user_sim)
    return combined_scores.head(top_n).index.tolist()

@app.route('/recommend', methods=['GET'])
def recommend_endpoint():
    user_id = int(request.args.get('user_id'))
    top_n = int(request.args.get('top_n', 5))
    interaction_matrix, content_sim_df, properties_df = load_data()
    recommendations = recommend(user_id, interaction_matrix, content_sim_df, properties_df, top_n)
    return jsonify({'recommended_property_ids': recommendations})

if __name__ == '__main__':
    app.run(debug=False, port=5050, host='127.0.0.1')
