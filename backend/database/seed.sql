USE campuseats;

-- All demo accounts use the password: 123456
INSERT INTO users (id, name, email, password_hash, role) VALUES
('10000000-0000-4000-8000-000000000001', 'Student Demo', 'student@test.com', '$2y$10$Sh04P.3/W1KAyTU/bV6Y2.dbdhlE72DmSsKKXijMvmIj63n.AHS9q', 'student'),
('10000000-0000-4000-8000-000000000002', 'Vendor Demo', 'vendor@test.com', '$2y$10$Sh04P.3/W1KAyTU/bV6Y2.dbdhlE72DmSsKKXijMvmIj63n.AHS9q', 'vendor'),
('10000000-0000-4000-8000-000000000003', 'Admin Demo', 'admin@test.com', '$2y$10$Sh04P.3/W1KAyTU/bV6Y2.dbdhlE72DmSsKKXijMvmIj63n.AHS9q', 'admin')
ON DUPLICATE KEY UPDATE
    name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role);

INSERT INTO vendors (id, owner_id, name, description, location, opening_hours, is_active) VALUES
('20000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000002',
 'Campus Nasi Corner', 'Malaysian comfort food, wok-fired fresh for campus pickup.', 'Food Court A', '9AM - 5PM', 1)
ON DUPLICATE KEY UPDATE
    owner_id = VALUES(owner_id), name = VALUES(name), description = VALUES(description),
    location = VALUES(location), opening_hours = VALUES(opening_hours), is_active = 1;

INSERT INTO menu_items (id, vendor_id, name, description, price, in_stock) VALUES
('30000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', 'Nasi Goreng Kampung', 'Smoky fried rice with anchovies, egg and vegetables.', 7.50, 1),
('30000000-0000-4000-8000-000000000002', '20000000-0000-4000-8000-000000000001', 'Chicken Rice', 'Tender chicken, fragrant rice and house chilli sauce.', 8.00, 1),
('30000000-0000-4000-8000-000000000003', '20000000-0000-4000-8000-000000000001', 'Vegetarian Fried Noodles', 'Wok-fried noodles with tofu and seasonal vegetables.', 6.50, 1),
('30000000-0000-4000-8000-000000000004', '20000000-0000-4000-8000-000000000001', 'Spicy Chicken Wrap', 'Grilled chicken, crisp salad and sambal mayo.', 9.00, 1),
('30000000-0000-4000-8000-000000000005', '20000000-0000-4000-8000-000000000001', 'Iced Milo', 'Classic chilled chocolate malt drink.', 3.00, 1),
('30000000-0000-4000-8000-000000000006', '20000000-0000-4000-8000-000000000001', 'Teh O Ais Limau', 'Iced black tea with fresh lime.', 2.50, 1)
ON DUPLICATE KEY UPDATE
    vendor_id = VALUES(vendor_id), name = VALUES(name), description = VALUES(description),
    price = VALUES(price), in_stock = VALUES(in_stock);

-- Dates follow the day the seed is imported so today's analytics always has demo data.
INSERT INTO orders (id, customer_id, vendor_id, pickup_at, total, status, created_at) VALUES
('40000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', TIMESTAMP(CURDATE(), '12:30:00'), 10.50, 'placed', TIMESTAMP(CURDATE(), '10:05:00')),
('40000000-0000-4000-8000-000000000002', '10000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', TIMESTAMP(CURDATE(), '12:45:00'), 8.00, 'preparing', TIMESTAMP(CURDATE(), '10:20:00')),
('40000000-0000-4000-8000-000000000003', '10000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', TIMESTAMP(CURDATE(), '13:00:00'), 9.00, 'ready', TIMESTAMP(CURDATE(), '11:10:00')),
('40000000-0000-4000-8000-000000000004', '10000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', TIMESTAMP(CURDATE(), '11:45:00'), 15.00, 'collected', TIMESTAMP(CURDATE(), '09:40:00'))
ON DUPLICATE KEY UPDATE
    pickup_at = VALUES(pickup_at), total = VALUES(total), status = VALUES(status),
    created_at = VALUES(created_at);

INSERT INTO order_items (id, order_id, menu_item_id, qty, unit_price) VALUES
('50000000-0000-4000-8000-000000000001', '40000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000001', 1, 7.50),
('50000000-0000-4000-8000-000000000002', '40000000-0000-4000-8000-000000000001', '30000000-0000-4000-8000-000000000005', 1, 3.00),
('50000000-0000-4000-8000-000000000003', '40000000-0000-4000-8000-000000000002', '30000000-0000-4000-8000-000000000002', 1, 8.00),
('50000000-0000-4000-8000-000000000004', '40000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000003', 1, 6.50),
('50000000-0000-4000-8000-000000000005', '40000000-0000-4000-8000-000000000003', '30000000-0000-4000-8000-000000000006', 1, 2.50),
('50000000-0000-4000-8000-000000000006', '40000000-0000-4000-8000-000000000004', '30000000-0000-4000-8000-000000000001', 2, 7.50)
ON DUPLICATE KEY UPDATE
    order_id = VALUES(order_id), menu_item_id = VALUES(menu_item_id),
    qty = VALUES(qty), unit_price = VALUES(unit_price);

INSERT INTO reviews (id, student_id, vendor_id, rating, comment, created_at) VALUES
('60000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', '20000000-0000-4000-8000-000000000001', 5, 'Fast service and good food.', TIMESTAMP(CURDATE(), '14:00:00'))
ON DUPLICATE KEY UPDATE
    rating = VALUES(rating), comment = VALUES(comment), created_at = VALUES(created_at);

INSERT INTO notifications (id, user_id, message, is_read, created_at) VALUES
('70000000-0000-4000-8000-000000000001', '10000000-0000-4000-8000-000000000001', 'Your order is ready for pickup.', 0, TIMESTAMP(CURDATE(), '11:15:00'))
ON DUPLICATE KEY UPDATE
    message = VALUES(message), is_read = VALUES(is_read), created_at = VALUES(created_at);
