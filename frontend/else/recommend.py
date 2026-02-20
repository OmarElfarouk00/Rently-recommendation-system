import pandas as pd
import numpy as np
from sklearn.metrics.pairwise import cosine_similarity
from sqlalchemy import create_engine


# Step 1: Connect to Database
DB_URI = "mysql+pymysql://root:@localhost/propertymanagementsystem1"
engine = create_engine(DB_URI)

# Step 2: Load Data
rentals = pd.read_sql("SELECT id_client, id_property FROM Rental", engine)
favorites = pd.read_sql("SELECT id_client, id_property FROM Favorits", engine)
properties = pd.read_sql(
    "SELECT id_property, propertyType, address FROM Property", engine
)

# Merge interactions
data = pd.concat([rentals, favorites], ignore_index=True).drop_duplicates()
data["interaction"] = 1


# Step 3: Create User-Item Matrix
interaction_matrix = data.pivot_table(
    index="id_client", columns="id_property", values="interaction", fill_value=0
)


# Step 4: Content Feature Encoding
properties["propertyType"] = properties["propertyType"].astype("category").cat.codes
properties["address"] = properties["address"].astype("category").cat.codes

content_features = properties.set_index("id_property")[["propertyType", "address"]]


# Step 5: Compute Similarities

# Item-to-item (content-based)
content_sim = cosine_similarity(content_features)
content_sim_df = pd.DataFrame(
    content_sim, index=content_features.index, columns=content_features.index
)

# User-to-item (collaborative filtering)
user_similarity = cosine_similarity(interaction_matrix)
user_sim_df = pd.DataFrame(
    user_similarity, index=interaction_matrix.index, columns=interaction_matrix.index
)



# Step 6: Recommend for a User
def recommend(user_id, top_n=5):
    if user_id not in interaction_matrix.index:
        return []

    user_vector = interaction_matrix.loc[user_id]
    interacted_items = user_vector[user_vector > 0].index

    # Score items using content similarity
    content_scores = content_sim_df[interacted_items].mean(axis=1)
    content_scores = content_scores.drop(interacted_items, errors="ignore")

    # Score items using similar users
    similar_users = user_sim_df[user_id].sort_values(ascending=False)[1:6]
    similar_user_ids = similar_users.index
    similar_user_items = interaction_matrix.loc[similar_user_ids].mean(axis=0)
    similar_user_items = similar_user_items.drop(interacted_items, errors="ignore")

    # Combine scores
    combined_scores = (
        (content_scores + similar_user_items).dropna().sort_values(ascending=False)
    )

    return combined_scores.head(top_n).index.tolist()


user_id = 2
recommended_ids = recommend(user_id)
print(f"Recommended property IDs for user {user_id}: {recommended_ids}")


