<?php
class DBController {
    private $host = "localhost";
    private $user = "root";
    private $password = "mhr12345";
    private $connection;

    function __construct() {
        // Create connection to MySQL server
        $this->connection = mysqli_connect($this->host, $this->user, $this->password);

        if (!$this->connection) {
            die("Connection failed: " . mysqli_connect_error());
        }

        // Check for shopify shop domin name in the incoming request
        $request = json_decode(file_get_contents('php://input'), true);

        $shopDomain = $request["shop"] ?? "";
        $shopDomain =  str_replace(".myshopify.com","", $shopDomain);
        $databaseName = preg_replace("/[^A-Za-z0-9 ]/", '_', $shopDomain);

        if ($databaseName) {
            $database = "dr_$databaseName";

            $dbFound = $this->checkDatabase($database);
            if($dbFound) {
                mysqli_close($this->connection);
                $this->connection = mysqli_connect($this->host, $this->user, $this->password, $database);
                mysqli_select_db($this->connection, $database);
            }
        } else {
            die("No database specified.");
        }
    }

    public function checkDatabase($dbName) {
        $query="SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME=?";
        $stmt = $this->connection->prepare($query);
        $stmt->bind_param('s', $dbName);
        $stmt->execute();
        $stmt->bind_result($data);
        // Check if the database exists
        if($stmt->fetch()) {
            // Connect to database
            $stmt->close();
            return true;
        }
        else {
            // Database does not exist, create it
            $stmt->close();
            return $this->createDatabase($dbName);
        }
    }

    private function createDatabase($dbName) {
        $createDbQuery = "CREATE DATABASE `$dbName`";
        if (mysqli_query($this->connection, $createDbQuery)) {
            // Select the new database
            mysqli_select_db($this->connection, $dbName);
            // Run your create table queries here
            return $this->createTables();
        } else {
            die("Error creating database: " . mysqli_error($this->connection));
        }
    }

    private function createTables() {
        // Example create table query
        $createTableQuery = "
            START TRANSACTION;
            CREATE TABLE IF NOT EXISTS orders(
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                dr_discount_applied BOOLEAN,
                dr_discount_amounts TEXT,
                dr_discount_total DECIMAL(10, 2),
                order_id BIGINT,
                order_gid VARCHAR(255),
                app_id INT,
                browser_ip VARCHAR(45),
                buyer_accepts_marketing BOOLEAN,
                cancel_reason VARCHAR(255),
                cancelled_at DATETIME,
                cart_token VARCHAR(255),
                checkout_id BIGINT,
                checkout_token VARCHAR(255),
                client_details TEXT,
                closed_at DATETIME,
                company VARCHAR(255),
                confirmation_number VARCHAR(255),
                confirmed BOOLEAN,
                contact_email VARCHAR(255),
                created_at DATETIME,
                currency VARCHAR(10),
                current_subtotal_price DECIMAL(10, 2),
                current_subtotal_price_set TEXT,
                current_total_additional_fees_set TEXT,
                current_total_discounts DECIMAL(10, 2),
                current_total_discounts_set TEXT,
                current_total_duties_set TEXT,
                current_total_price DECIMAL(10, 2),
                current_total_price_set TEXT,
                current_total_tax DECIMAL(10, 2),
                current_total_tax_set TEXT,
                customer_locale VARCHAR(10),
                device_id VARCHAR(255),
                discount_codes TEXT,
                email VARCHAR(255),
                estimated_taxes BOOLEAN,
                financial_status VARCHAR(255),
                fulfillment_status VARCHAR(255),
                landing_site VARCHAR(255),
                landing_site_ref VARCHAR(255),
                location_id BIGINT,
                merchant_of_record_app_id BIGINT,
                NAME VARCHAR(255),
                note TEXT,
                note_attributes TEXT,
                NUMBER INT,
                order_number INT,
                order_status_url TEXT,
                original_total_additional_fees_set TEXT,
                original_total_duties_set TEXT,
                payment_gateway_names TEXT,
                po_number VARCHAR(255),
                presentment_currency VARCHAR(10),
                processed_at DATETIME,
                reference VARCHAR(255),
                referring_site VARCHAR(255),
                source_identifier VARCHAR(255),
                source_name VARCHAR(255),
                source_url TEXT,
                subtotal_price DECIMAL(10, 2),
                subtotal_price_set TEXT,
                tags TEXT,
                tax_exempt BOOLEAN,
                tax_lines TEXT,
                taxes_included BOOLEAN,
                test BOOLEAN,
                token VARCHAR(255),
                total_discounts DECIMAL(10, 2),
                total_discounts_set TEXT,
                total_line_items_price DECIMAL(10, 2),
                total_line_items_price_set TEXT,
                total_outstanding DECIMAL(10, 2),
                total_price DECIMAL(10, 2),
                total_price_set TEXT,
                total_shipping_price_set TEXT,
                total_tax DECIMAL(10, 2),
                total_tax_set TEXT,
                total_tip_received DECIMAL(10, 2),
                total_weight INT,
                updated_at DATETIME,
                user_id BIGINT,
                billing_address TEXT,
                customer TEXT,
                discount_applications TEXT,
                fulfillments TEXT,
                line_items TEXT,
                payment_terms TEXT,
                refunds TEXT,
                shipping_address TEXT,
                shipping_lines TEXT,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS order_line_items(
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT,
                line_item_id BIGINT,
                line_item_gid VARCHAR(255),
                attributed_staffs TEXT,
                current_quantity INT,
                fulfillable_quantity INT,
                fulfillment_service VARCHAR(50),
                fulfillment_status VARCHAR(50),
                gift_card BOOLEAN,
                grams DECIMAL(10, 2),
                NAME VARCHAR(255),
                price DECIMAL(10, 2),
                price_set TEXT,
                product_exists BOOLEAN,
                product_id BIGINT,
                properties TEXT,
                quantity INT,
                requires_shipping BOOLEAN,
                sku VARCHAR(100),
                taxable BOOLEAN,
                title VARCHAR(255),
                total_discount DECIMAL(10, 2),
                total_discount_set TEXT,
                variant_id BIGINT,
                variant_inventory_management VARCHAR(50),
                variant_title VARCHAR(255),
                vendor VARCHAR(255),
                tax_lines TEXT,
                duties TEXT,
                discount_allocations TEXT,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS checkouts (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                checkout_id BIGINT,
                token VARCHAR(255),
                cart_token VARCHAR(255),
                email VARCHAR(255),
                gateway TEXT, 
                buyer_accepts_marketing BOOLEAN,
                buyer_accepts_sms_marketing BOOLEAN,
                created_at DATETIME,
                updated_at DATETIME,
                landing_site VARCHAR(255),
                note TEXT,
                note_attributes TEXT,
                referring_site VARCHAR(255),
                shipping_lines TEXT,
                shipping_address TEXT,
                taxes_included BOOLEAN,
                total_weight INTEGER,
                currency VARCHAR(10),
                completed_at DATETIME,
                customer_locale VARCHAR(10),
                name VARCHAR(50),
                abandoned_checkout_url TEXT,
                discount_codes TEXT,
                tax_lines TEXT,
                presentment_currency VARCHAR(10),
                source_name VARCHAR(50),
                total_line_items_price DECIMAL(10, 2),
                total_tax DECIMAL(10, 2),
                total_discounts DECIMAL(10, 2),
                subtotal_price DECIMAL(10, 2),
                total_price DECIMAL(10, 2),
                total_duties DECIMAL(10, 2),
                device_id BIGINT,
                user_id BIGINT,
                location_id BIGINT,
                source_identifier VARCHAR(255),
                source_url TEXT,
                source TEXT,
                closed_at DATETIME,
                billing_address TEXT,
                customer TEXT,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS checkout_line_items (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                checkout_id BIGINT,
                checkout_line_item_key VARCHAR(255),
                fulfillment_service VARCHAR(255),
                gift_card BOOLEAN,
                grams INT,
                presentment_title VARCHAR(255),
                presentment_variant_title VARCHAR(255),
                product_id BIGINT,
                quantity INT,
                requires_shipping BOOLEAN,
                sku VARCHAR(255),
                tax_lines TEXT,
                taxable BOOLEAN,
                title VARCHAR(255),
                variant_id BIGINT,
                variant_title VARCHAR(255),
                variant_price DECIMAL(10, 2),
                vendor VARCHAR(255),
                unit_price_measurement TEXT,
                compare_at_price DECIMAL(10, 2),
                line_price DECIMAL(10, 2),
                price DECIMAL(10, 2),
                applied_discounts TEXT,
                destination_location_id BIGINT,
                user_id BIGINT,
                checkout_line_item_rank INT,
                origin_location_id BIGINT,
                properties TEXT,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS carts(
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                cart_id VARCHAR(255),
                token VARCHAR(255),
                note TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS cart_line_items(
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                cart_id BIGINT,
                line_item_id BIGINT,
                properties TEXT,
                quantity INT,
                variant_id BIGINT,
                line_item_key VARCHAR(255),
                discounted_price DECIMAL(10, 2),
                discounted_price_set TEXT,
                discounts TEXT,
                gift_card BOOLEAN,
                grams DECIMAL(10, 2),
                line_price DECIMAL(10, 2),
                line_price_set TEXT,
                original_line_price DECIMAL(10, 2),
                original_line_price_set TEXT,
                original_price DECIMAL(10, 2),
                price DECIMAL(10, 2),
                price_set TEXT,
                product_id BIGINT,
                sku VARCHAR(255),
                taxable BOOLEAN,
                title VARCHAR(255),
                total_discount DECIMAL(10, 2),
                total_discount_set TEXT,
                vendor VARCHAR(255),
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP,
                destroyed_at TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS discounts(
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                discount_id INT,
                title VARCHAR(255),
                order_count INT DEFAULT 0,
                product_count INT DEFAULT 0,
                variant_count INT DEFAULT 0,
                total_discount DECIMAL(10, 2) DEFAULT 0.0,
                stored_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_at TIMESTAMP
            );
            COMMIT;
        ";

        if (mysqli_multi_query($this->connection, $createTableQuery)) {
            echo "Table created successfully.";
            return true;
        } else {
            die("Error creating table: " . mysqli_error($this->connection));
        }
    }

    function getConnection() {
        return $this->connection;
    }

    function executeQuery($query) {
        return mysqli_query($this->connection, $query);
    }

    function __destruct() {
        mysqli_close($this->connection);
    }
}

