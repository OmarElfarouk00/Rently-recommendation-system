# Rently – Intelligent Property Rental & Recommendation Platform

## 📌 Overview

Rently is a full-stack property management and rental platform enhanced with a content-based recommendation system.

The platform allows property owners to publish listings and clients to browse, interact, and receive personalized property recommendations based on their preferences and interaction history.

This project was developed as a graduation project (PFE) in Information Systems with a focus on recommendation systems.

---

## 🚀 Key Features

- Property listing and management (Apartment, Villa, Room, House)
- User authentication system (Clients & Property Owners)
- Rental and negotiation management
- Admin dashboard
- Content-based recommendation system
- Personalized Top-N property suggestions

---

## 🧠 Recommendation System Logic

The recommendation engine is based on:

- Feature extraction from property attributes (type, location.)
- Vector representation of properties
- Cosine similarity calculation
- Top-N property ranking

This approach enables personalized suggestions without requiring collaborative filtering.

---

## 🛠️ Technologies Used

### Backend
- Python
- Pandas
- SQLAlchemy
- Cosine Similarity
- REST API structure

### Frontend
- PHP
- HTML
- CSS
- JavaScript
- Ajax

### Database
- MySQL (Relational database design with specialization tables)

---

## 🗄 Database Design

The system includes the following main entities:

- Client
- PropertyOwner
- Property
- Apartment
- Villa
- Room
- House
- Rental
- Negotiation

The database schema supports property specialization and scalable analytics.

---

## ⚙️ Setup Instructions

### Backend (Python)

1. Create a virtual environment:

   python -m venv venv
   source venv/bin/activate  # Windows: venv\Scripts\activate

2. Install dependencies:

   pip install -r requirements.txt

3. Run the API (example):

   python backend/api/recommendation_api.py

## Requirements 
See requirements.txt for Python dependencies.

## License 
MIT License