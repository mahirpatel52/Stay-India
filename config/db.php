<?php
session_start();

$host = "localhost";
$dbUser = "root";
$dbPass = "";
$dbName = "hotel_booking";

$conn = mysqli_connect($host, $dbUser, $dbPass, $dbName);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Keep users table compatible with extended profile fields.
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(20) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS dob DATE DEFAULT NULL");

// Keep hotels table compatible with richer detail fields.
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS hotel_images TEXT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS rating DECIMAL(2,1) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS room_type VARCHAR(100) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS amenities TEXT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS hotel_rules TEXT DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS check_in_time VARCHAR(30) DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE hotels ADD COLUMN IF NOT EXISTS check_out_time VARCHAR(30) DEFAULT NULL");

// Auto-fill richer descriptions and hotel-specific rules for seeded hotels.
$hotelContentMap = [
    "The Oberoi Udaivilas" => [
        "description" => "Set on the banks of Lake Pichola, The Oberoi Udaivilas offers a palace-inspired stay with hand-painted interiors, private courtyards, and serene water views from most premium rooms. Guests enjoy personalized butler assistance, curated cultural evenings, and multi-cuisine fine dining with a strong focus on Rajasthani hospitality. The property is ideal for couples, families, and travelers seeking a luxury heritage experience close to Udaipur's main attractions.",
        "rules" => "Valid government ID required at check-in.\nPrimary guest must be 18+.\nNo loud music or parties after 10:30 PM.\nOutside guests allowed only in lobby with approval.\nPool use in proper swimwear only.\nPets not allowed.\nNo smoking in non-smoking rooms."
    ],
    "Taj Lake Palace" => [
        "description" => "Located in the middle of Lake Pichola, Taj Lake Palace delivers a unique island-palace experience with boat transfers, premium suites, and panoramic sunset views. Interiors feature traditional royal decor with modern comfort, and the hotel is known for curated dining, spa therapies, and signature Taj service standards. It is a preferred choice for luxury vacations, anniversary stays, and premium leisure travel.",
        "rules" => "Mandatory photo ID for all guests.\nCheck-in allowed for adults 18 years and above.\nBoat transfer timings must be followed.\nQuiet hours from 10:00 PM to 7:00 AM.\nNo damage to heritage property areas.\nNo bachelor parties without prior approval.\nSmoking only in designated zones."
    ],
    "The Leela Palace" => [
        "description" => "The Leela Palace New Delhi combines contemporary design with classic Indian luxury in the diplomatic district. Rooms are spacious with premium bedding, marble bathrooms, and city-facing views. Business and leisure travelers benefit from multiple dining venues, wellness facilities, conference-ready spaces, and quick access to central Delhi landmarks and embassies.",
        "rules" => "Government-approved ID proof is compulsory.\nEarly check-in/late checkout subject to availability.\nUnmarried couples allowed with valid IDs.\nNo illegal substances on property.\nRoom occupancy must not exceed booking limit.\nPets allowed only in designated room categories.\nAny loss/damage may be chargeable."
    ],
    "Taj Falaknuma Palace" => [
        "description" => "Perched above Hyderabad, Taj Falaknuma Palace offers a grand heritage stay with royal architecture, curated palace tours, and elegant suites inspired by Nizam-era design. Guests can enjoy fine-dining experiences, premium wellness treatments, and panoramic city views from elevated gardens and terraces. The property is suitable for high-end leisure stays and special occasions.",
        "rules" => "Valid ID required at check-in for all occupants.\nPalace tour timings are fixed and must be respected.\nFormal dress code may apply for select dining areas.\nNo outside food or alcohol permitted.\nNoise restrictions apply after 10:00 PM.\nChildren must be supervised in heritage sections.\nEvent photography requires prior permission."
    ],
    "The Oberoi Amarvilas" => [
        "description" => "The Oberoi Amarvilas in Agra is known for uninterrupted Taj Mahal views from many rooms and public spaces. The resort-style property offers landscaped gardens, premium suites, private dining options, and attentive concierge services for monument visits. It is designed for guests looking for a refined, scenic, and culturally rich luxury stay experience.",
        "rules" => "Original ID mandatory at arrival.\nPrimary guest must be at least 18 years old.\nEntry/exit to Taj-view terraces may follow schedule.\nNo drone photography without legal approval.\nPool timings must be followed.\nNo smoking in indoor common areas.\nPets not permitted unless pre-approved."
    ],
    "ITC Grand Chola" => [
        "description" => "ITC Grand Chola in Chennai is a landmark business-luxury hotel inspired by Chola architecture and modern comfort. It offers spacious rooms, multiple award-winning restaurants, wellness facilities, and strong connectivity to corporate hubs and airport routes. The property works well for business stays, family travel, and premium city getaways.",
        "rules" => "Photo ID and address proof required.\nCheck-in allowed for guests aged 18+.\nCorporate bookings must follow company policy docs.\nNo parties or events in rooms without approval.\nOutside visitors restricted after 9:00 PM.\nSmoking allowed only in designated rooms/zones.\nValuables should be kept in room safe."
    ],
    "Rambagh Palace" => [
        "description" => "Rambagh Palace Jaipur, once a royal residence, delivers an immersive heritage-luxury stay with grand corridors, manicured gardens, and palace-style suites. Guests experience traditional hospitality, fine dining, and curated activities including heritage walks and wellness sessions. The property is ideal for premium cultural travel and destination celebrations.",
        "rules" => "Government ID mandatory for all guests.\nRespect heritage property guidelines.\nLoud music/celebrations after 10:30 PM are restricted.\nProfessional shoots need prior written permission.\nChildren must be accompanied in pool and garden areas.\nNo smoking in heritage interiors.\nPet entry only with prior confirmation."
    ],
    "The St. Regis" => [
        "description" => "The St. Regis Mumbai is a luxury high-rise hotel with panoramic skyline views, modern suites, and premium dining experiences. Located in Lower Parel, it offers strong access to business districts, shopping, and nightlife. With personalized service and contemporary design, it suits both corporate and leisure travelers seeking upscale urban comfort.",
        "rules" => "Valid ID proof required at check-in.\nLocal ID policies may apply as per hotel norms.\nRoom occupancy limits must be strictly followed.\nNo illegal activities or prohibited items allowed.\nQuiet hours start at 10:00 PM.\nPool/gym access subject to operational timings.\nAny incidental charges payable at checkout."
    ],
    "The Tamara Coorg" => [
        "description" => "The Tamara Coorg is a scenic hillside retreat surrounded by coffee plantations and valley viewpoints. Guests stay in spacious cottages with private decks and enjoy nature trails, local cuisine, and wellness experiences in a quiet eco-friendly setting. It is ideal for couples, weekend escapes, and travelers looking for calm premium hospitality in nature.",
        "rules" => "Valid ID required for check-in.\nGuests must follow eco-sensitive property rules.\nNo loud music in outdoor areas after 9:30 PM.\nBonfire activities only in designated zones.\nLeech/safety advisories for nature trails must be followed.\nOutside food delivery may be restricted.\nSmoking only in permitted areas."
    ],
    "Kumarakom Lake Resort" => [
        "description" => "Kumarakom Lake Resort offers a premium backwater experience with Kerala-style villas, waterfront dining, and relaxing leisure spaces. The resort blends traditional architecture with modern amenities, making it suitable for family vacations, honeymoon trips, and calm long-weekend stays. Guests can enjoy spa therapies, boat experiences, and curated cultural touches throughout the property.",
        "rules" => "Photo ID mandatory at check-in.\nPrimary guest should be 18 years or older.\nBackwater activity timings are weather dependent.\nNo littering near water-facing zones.\nPool use requires proper swimwear.\nRoom service and restaurant timings must be respected.\nDamage to property items may incur charges."
    ]
];

$hotelContentSql = "UPDATE hotels
                    SET description = CASE
                        WHEN description IS NULL OR CHAR_LENGTH(TRIM(description)) < 120 THEN ?
                        ELSE description
                    END,
                    hotel_rules = CASE
                        WHEN hotel_rules IS NULL OR CHAR_LENGTH(TRIM(hotel_rules)) = 0 THEN ?
                        ELSE hotel_rules
                    END
                    WHERE name = ?";
$hotelContentStmt = mysqli_prepare($conn, $hotelContentSql);
if ($hotelContentStmt) {
    foreach ($hotelContentMap as $hotelName => $content) {
        $desc = $content["description"];
        $rules = $content["rules"];
        mysqli_stmt_bind_param($hotelContentStmt, "sss", $desc, $rules, $hotelName);
        mysqli_stmt_execute($hotelContentStmt);
    }
    mysqli_stmt_close($hotelContentStmt);
}

// Auto-fill hotel gallery photos when gallery is empty.
$hotelGalleryResult = mysqli_query($conn, "SELECT hotel_id, name, city, hotel_images FROM hotels");
if ($hotelGalleryResult) {
    $galleryUpdateSql = "UPDATE hotels SET hotel_images = ? WHERE hotel_id = ?";
    $galleryUpdateStmt = mysqli_prepare($conn, $galleryUpdateSql);

    if ($galleryUpdateStmt) {
        while ($hotelRow = mysqli_fetch_assoc($hotelGalleryResult)) {
            $existingGallery = trim((string)($hotelRow["hotel_images"] ?? ""));
            if ($existingGallery !== "") {
                continue;
            }

            $citySeed = urlencode(strtolower((string)($hotelRow["city"] ?? "hotel")));
            $nameSeed = urlencode(strtolower((string)($hotelRow["name"] ?? "stay")));
            $seedBase = ((int)$hotelRow["hotel_id"] * 100) + 1000;
            $tags = [
                "luxury hotel exterior",
                "luxury hotel lobby interior",
                "luxury hotel room bedroom",
                "luxury hotel bathroom",
                "luxury hotel swimming pool",
                "luxury hotel garden resort",
                "luxury hotel restaurant dining"
            ];

            $galleryUrls = [];
            foreach ($tags as $idx => $tag) {
                $galleryUrls[] = "https://source.unsplash.com/1200x800/?" .
                    urlencode($tag) . "," . $citySeed . "," . $nameSeed .
                    "&sig=" . ($seedBase + $idx + 1);
            }

            $galleryText = implode("\n", $galleryUrls);
            $hotelIdForUpdate = (int)$hotelRow["hotel_id"];
            mysqli_stmt_bind_param($galleryUpdateStmt, "si", $galleryText, $hotelIdForUpdate);
            mysqli_stmt_execute($galleryUpdateStmt);
        }
        mysqli_stmt_close($galleryUpdateStmt);
    }
}

// Keep bookings table compatible with stay range.
mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS check_in_date DATE DEFAULT NULL");
mysqli_query($conn, "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS check_out_date DATE DEFAULT NULL");

// Auto-create payments table so payment feature works without manual migration.
$createPaymentsTableSql = "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payer_reference VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(80) NOT NULL UNIQUE,
    payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
    paid_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
)";
mysqli_query($conn, $createPaymentsTableSql);

define("BASE_URL", "/hotel_booking");
define("ADMIN_EMAIL", "mahirfaldu1.yt@gmail.com");
define("ADMIN_PASSWORD", "mahir123");

// OTP mail settings (change these to your SMTP provider details).
define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", "587");
define("SMTP_FROM", "youremail@gmail.com");
define("SMTP_FROM_NAME", "Stay India");
define("SMTP_USERNAME", "youremail@gmail.com");
define("SMTP_PASSWORD", "your_app_password");
define("SMTP_ENCRYPTION", "tls"); // tls or ssl
define("SMTP_TIMEOUT", 15);
?>
