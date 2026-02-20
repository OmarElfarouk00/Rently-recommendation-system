# Rewriting with pre-processed strings to avoid backslash in f-strings

# Reinitializing data containers
properties = []
apartments = []
houses = []
rooms = []
villas = []

# Generate 50 properties
for i in range(1, 51):
    prop_type = property_types[(i - 1) % 4]  # Cycle through each type
    id_property = i
    id_owner = random.randint(1, 20)
    title = f"{prop_type.capitalize()} {fake.street_name()}"
    description = fake.sentence()
    size = random.randint(20, 400)
    bedrooms = random.randint(1, 5)
    bathrooms = random.randint(1, 3)
    address = fake.street_address().replace("'", "")
    city = "Oran"
    state = "Oran"
    country = "Algeria"
    social_code = f"SC{i:03d}"
    estimate_price = random.randint(30000, 800000)
    status = random.choice(statuses)
    owner_need = random.choice(owner_needs)
    properties.append(f"INSERT INTO Property VALUES ({id_property}, {id_owner}, '{title}', '{description}', '{prop_type}', {size}, {bedrooms}, {bathrooms}, '{address}', '{city}', '{state}', '{country}', '{social_code}', {estimate_price}, '{status}', '{owner_need}');")

    # Specific table inserts
    if prop_type == 'apartment':
        building_name = fake.company().replace("'", "")
        has_elevator = random.choice(['true', 'false'])
        has_parking = random.choice(['true', 'false'])
        fee = random.randint(50, 300)
        apartments.append(f"INSERT INTO Apartment VALUES ({i}, {id_property}, {random.randint(1, 20)}, '{building_name}', {has_elevator}, {has_parking}, {fee});")
    elif prop_type == 'house':
        houses.append(f"INSERT INTO House VALUES ({i}, {id_property}, {random.randint(1, 3)}, {random.randint(50, 200)}, {random.choice(['true', 'false'])}, {random.randint(0, 3)}, {random.choice(['true', 'false'])});")
    elif prop_type == 'room':
        rooms.append(f"INSERT INTO Room VALUES ({i}, {id_property}, '{random.choice(['single', 'double'])}', {random.randint(1, 10)}, {random.choice(['true', 'false'])}, {random.choice(['true', 'false'])}, {random.choice(['true', 'false'])});")
    elif prop_type == 'villa':
        villas.append(f"INSERT INTO Villa VALUES ({i}, {id_property}, {random.randint(1, 3)}, {random.randint(100, 300)}, {random.choice(['true', 'false'])}, {random.choice(['true', 'false'])}, {random.randint(1, 3)}, {random.choice(['true', 'false'])});")

(properties[:5], apartments[:2], houses[:2], rooms[:2], villas[:2])  # Preview some of the queries
