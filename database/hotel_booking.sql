CREATE DATABASE IF NOT EXISTS hotel_booking;
USE hotel_booking;

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    dob DATE DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS hotels (
    hotel_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    hotel_images TEXT DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    rating DECIMAL(2,1) DEFAULT NULL,
    room_type VARCHAR(100) DEFAULT NULL,
    amenities TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    hotel_rules TEXT DEFAULT NULL,
    check_in_time VARCHAR(30) DEFAULT NULL,
    check_out_time VARCHAR(30) DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    date DATE NOT NULL,
    check_in_date DATE DEFAULT NULL,
    check_out_date DATE DEFAULT NULL,
    CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payer_reference VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(80) NOT NULL UNIQUE,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    paid_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

INSERT INTO hotels (name, city, price, image, address, rating, room_type, amenities, description, hotel_rules, check_in_time, check_out_time) VALUES
('The Oberoi Udaivilas', 'Udaipur', 4200.00, 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1200&q=80', 'Pichola, Udaipur, Rajasthan', 4.9, 'Premier Lake View Room', 'Pool, Spa, WiFi, Breakfast, Airport Transfer', 'Set on the banks of Lake Pichola, The Oberoi Udaivilas offers a palace-inspired stay with hand-painted interiors, private courtyards, and serene water views from most premium rooms. Guests enjoy personalized butler assistance, curated cultural evenings, and multi-cuisine fine dining with a strong focus on Rajasthani hospitality. The property is ideal for couples, families, and travelers seeking a luxury heritage experience close to Udaipur attractions.', 'Valid government ID required at check-in.\nPrimary guest must be 18+.\nNo loud music or parties after 10:30 PM.\nOutside guests allowed only in lobby with approval.\nPool use in proper swimwear only.\nPets not allowed.\nNo smoking in non-smoking rooms.', '2:00 PM', '11:00 AM'),
('Taj Lake Palace', 'Udaipur', 5100.00, 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?auto=format&fit=crop&w=1200&q=80', 'Lake Pichola, Udaipur, Rajasthan', 4.8, 'Royal Suite', 'Butler Service, Boat Transfer, Spa, Fine Dining', 'Located in the middle of Lake Pichola, Taj Lake Palace delivers a unique island-palace experience with boat transfers, premium suites, and panoramic sunset views. Interiors feature traditional royal decor with modern comfort, and the hotel is known for curated dining, spa therapies, and signature Taj service standards. It is a preferred choice for luxury vacations and premium leisure travel.', 'Mandatory photo ID for all guests.\nCheck-in allowed for adults 18 years and above.\nBoat transfer timings must be followed.\nQuiet hours from 10:00 PM to 7:00 AM.\nNo damage to heritage property areas.\nNo bachelor parties without prior approval.\nSmoking only in designated zones.', '2:00 PM', '12:00 PM'),
('The Leela Palace', 'New Delhi', 3600.00, 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80', 'Diplomatic Enclave, New Delhi', 4.7, 'Grand Deluxe', 'WiFi, Gym, Spa, Rooftop Pool, Breakfast', 'The Leela Palace New Delhi combines contemporary design with classic Indian luxury in the diplomatic district. Rooms are spacious with premium bedding, marble bathrooms, and city-facing views. Business and leisure travelers benefit from multiple dining venues, wellness facilities, conference-ready spaces, and quick access to central Delhi landmarks.', 'Government-approved ID proof is compulsory.\nEarly check-in/late checkout subject to availability.\nUnmarried couples allowed with valid IDs.\nNo illegal substances on property.\nRoom occupancy must not exceed booking limit.\nPets allowed only in designated room categories.\nAny loss/damage may be chargeable.', '3:00 PM', '12:00 PM'),
('Taj Falaknuma Palace', 'Hyderabad', 3900.00, 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1200&q=80', 'Falaknuma, Hyderabad, Telangana', 4.8, 'Palace Room', 'Heritage Tour, Pool, Spa, Dining, Concierge', 'Perched above Hyderabad, Taj Falaknuma Palace offers a grand heritage stay with royal architecture, curated palace tours, and elegant suites inspired by Nizam-era design. Guests can enjoy fine-dining experiences, premium wellness treatments, and panoramic city views from elevated gardens and terraces.', 'Valid ID required at check-in for all occupants.\nPalace tour timings are fixed and must be respected.\nFormal dress code may apply for select dining areas.\nNo outside food or alcohol permitted.\nNoise restrictions apply after 10:00 PM.\nChildren must be supervised in heritage sections.\nEvent photography requires prior permission.', '2:00 PM', '11:00 AM'),
('The Oberoi Amarvilas', 'Agra', 4500.00, 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1200&q=80', 'Taj East Gate Road, Agra, Uttar Pradesh', 4.9, 'Deluxe Room', 'Taj View, Spa, WiFi, Breakfast, Shuttle', 'The Oberoi Amarvilas in Agra is known for uninterrupted Taj Mahal views from many rooms and public spaces. The resort-style property offers landscaped gardens, premium suites, private dining options, and attentive concierge services for monument visits. It is designed for guests looking for a refined and scenic luxury stay experience.', 'Original ID mandatory at arrival.\nPrimary guest must be at least 18 years old.\nEntry/exit to Taj-view terraces may follow schedule.\nNo drone photography without legal approval.\nPool timings must be followed.\nNo smoking in indoor common areas.\nPets not permitted unless pre-approved.', '2:00 PM', '12:00 PM'),
('ITC Grand Chola', 'Chennai', 2800.00, 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1200&q=80', 'Guindy, Chennai, Tamil Nadu', 4.6, 'Executive Club', 'Pool, Gym, WiFi, Multi-cuisine Dining', 'ITC Grand Chola in Chennai is a landmark business-luxury hotel inspired by Chola architecture and modern comfort. It offers spacious rooms, multiple award-winning restaurants, wellness facilities, and strong connectivity to corporate hubs and airport routes. The property works well for business stays, family travel, and premium city getaways.', 'Photo ID and address proof required.\nCheck-in allowed for guests aged 18+.\nCorporate bookings must follow company policy docs.\nNo parties or events in rooms without approval.\nOutside visitors restricted after 9:00 PM.\nSmoking allowed only in designated rooms/zones.\nValuables should be kept in room safe.', '2:00 PM', '12:00 PM'),
('Rambagh Palace', 'Jaipur', 5400.00, 'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1200&q=80', 'Bhawani Singh Road, Jaipur, Rajasthan', 4.9, 'Palace Suite', 'Spa, Garden Dining, Pool, Butler Service', 'Rambagh Palace Jaipur, once a royal residence, delivers an immersive heritage-luxury stay with grand corridors, manicured gardens, and palace-style suites. Guests experience traditional hospitality, fine dining, and curated activities including heritage walks and wellness sessions. The property is ideal for premium cultural travel and destination celebrations.', 'Government ID mandatory for all guests.\nRespect heritage property guidelines.\nLoud music/celebrations after 10:30 PM are restricted.\nProfessional shoots need prior written permission.\nChildren must be accompanied in pool and garden areas.\nNo smoking in heritage interiors.\nPet entry only with prior confirmation.', '2:00 PM', '11:00 AM'),
('The St. Regis', 'Mumbai', 3400.00, 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1200&q=80', 'Lower Parel, Mumbai, Maharashtra', 4.7, 'Metropolitan Room', 'City View, Lounge Access, Pool, Spa', 'The St. Regis Mumbai is a luxury high-rise hotel with panoramic skyline views, modern suites, and premium dining experiences. Located in Lower Parel, it offers strong access to business districts, shopping, and nightlife. With personalized service and contemporary design, it suits both corporate and leisure travelers seeking upscale urban comfort.', 'Valid ID proof required at check-in.\nLocal ID policies may apply as per hotel norms.\nRoom occupancy limits must be strictly followed.\nNo illegal activities or prohibited items allowed.\nQuiet hours start at 10:00 PM.\nPool/gym access subject to operational timings.\nAny incidental charges payable at checkout.', '3:00 PM', '12:00 PM'),
('The Tamara Coorg', 'Coorg', 2600.00, 'https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1200&q=80', 'Yevakapadi, Coorg, Karnataka', 4.6, 'Luxury Cottage', 'Nature Trails, Pool, Spa, Breakfast', 'The Tamara Coorg is a scenic hillside retreat surrounded by coffee plantations and valley viewpoints. Guests stay in spacious cottages with private decks and enjoy nature trails, local cuisine, and wellness experiences in a quiet eco-friendly setting. It is ideal for couples and travelers looking for calm premium hospitality in nature.', 'Valid ID required for check-in.\nGuests must follow eco-sensitive property rules.\nNo loud music in outdoor areas after 9:30 PM.\nBonfire activities only in designated zones.\nLeech/safety advisories for nature trails must be followed.\nOutside food delivery may be restricted.\nSmoking only in permitted areas.', '1:00 PM', '11:00 AM'),
('Kumarakom Lake Resort', 'Kumarakom', 3000.00, 'https://images.unsplash.com/photo-1496417263034-38ec4f0b665a?auto=format&fit=crop&w=1200&q=80', 'Kumarakom, Kottayam, Kerala', 4.7, 'Heritage Villa', 'Backwater Cruise, Spa, Pool, WiFi', 'Kumarakom Lake Resort offers a premium backwater experience with Kerala-style villas, waterfront dining, and relaxing leisure spaces. The resort blends traditional architecture with modern amenities, making it suitable for family vacations, honeymoon trips, and calm long-weekend stays. Guests can enjoy spa therapies and curated cultural touches throughout the property.', 'Photo ID mandatory at check-in.\nPrimary guest should be 18 years or older.\nBackwater activity timings are weather dependent.\nNo littering near water-facing zones.\nPool use requires proper swimwear.\nRoom service and restaurant timings must be respected.\nDamage to property items may incur charges.', '2:00 PM', '11:00 AM');
