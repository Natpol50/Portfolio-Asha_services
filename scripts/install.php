<?php
/**
 * Installation script for the Portfolio Website
 * 
 * This script performs the following tasks:
 * 1. Creates necessary directories
 * 2. Generates a default .env file if one doesn't exist
 * 3. Creates the database schema if the database doesn't exist
 * 4. Populates the database with initial data
 */

// Define the root directory
$rootDir = dirname(__DIR__);

// Welcome message
echo "\n";
echo "╔═════════════════════════════╗\n";
echo "║      Aport Installer        ║\n";
echo "╚═════════════════════════════╝\n";
echo "\n";

// Step 1: Create necessary directories
echo "Step 1: Creating necessary directories...\n";

$directories = [
    $rootDir . '/var',
    $rootDir . '/var/cache',
    $rootDir . '/var/cache/twig',
    $rootDir . '/var/logs',
    $rootDir . '/public/assets',
    $rootDir . '/public/assets/img',
    $rootDir . '/public/assets/css',
    $rootDir . '/public/assets/js',
];

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        if (mkdir($directory, 0755, true)) {
            echo "  Created: $directory\n";
        } else {
            echo "  Failed to create: $directory\n";
        }
    } else {
        echo "  Already exists: $directory\n";
    }
}

// Step 2: Generate .env file if it doesn't exist
echo "\nStep 2: Checking .env file...\n";

$envFile = $rootDir . '/.env';
$envExampleFile = $rootDir . '/.env.example';

if (!file_exists($envFile) && file_exists($envExampleFile)) {
    echo "  .env file not found. Creating from .env.example...\n";
    
    // Copy the example file
    if (copy($envExampleFile, $envFile)) {
        echo "  .env file created successfully.\n";
        
        // Generate a random JWT secret
        $jwtSecret = bin2hex(random_bytes(32));
        
        // Replace the JWT secret placeholder
        $envContent = file_get_contents($envFile);
        $envContent = str_replace('your_jwt_secret_key', $jwtSecret, $envContent);
        file_put_contents($envFile, $envContent);
        
        echo "  Generated random JWT secret.\n";
    } else {
        echo "  Failed to create .env file.\n";
    }
} else {
    echo "  .env file already exists.\n";
}

// Load the .env file
$envContent = file_get_contents($envFile);
preg_match('/DB_HOST=(.*)/', $envContent, $hostMatches);
preg_match('/DB_USER=(.*)/', $envContent, $userMatches);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $passwordMatches);
preg_match('/DB_NAME=(.*)/', $envContent, $nameMatches);
preg_match('/DB_PORT=(.*)/', $envContent, $portMatches);

$dbHost = $hostMatches[1] ?? 'localhost';
$dbUser = $userMatches[1] ?? 'portfolio_user';
$dbPassword = $passwordMatches[1] ?? '';
$dbName = $nameMatches[1] ?? 'portfolio_db';
$dbPort = $portMatches[1] ?? '3306';

// Step 3: Create database if it doesn't exist
echo "\nStep 3: Setting up database...\n";

try {
    // Connect to MySQL without database
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $databaseExists = $stmt->rowCount() > 0;
    
    if (!$databaseExists) {
        echo "  Database '$dbName' not found. Creating...\n";
        $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  Database created successfully.\n";
    } else {
        echo "  Database '$dbName' already exists.\n";
    }
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) === 0) {
        echo "  No tables found. Creating schema...\n";
        
        // Read database schema SQL file
        $schemaFile = $rootDir . '/database/schema.sql';
        
        if (!file_exists($schemaFile)) {
            // Create directory if it doesn't exist
            if (!is_dir(dirname($schemaFile))) {
                mkdir(dirname($schemaFile), 0755, true);
            }
            
            // Create schema SQL file with default schema
            $schema = <<<SQL
-- Main database schema for multilingual portfolio website

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Languages supported by the system
CREATE TABLE languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE COMMENT 'ISO language code (e.g., "en", "fr")',
    name VARCHAR(50) NOT NULL COMMENT 'Language name in its own language',
    is_active BOOLEAN DEFAULT TRUE
);

-- Projects/Experiences
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Project type (e.g., "personal", "cesi", etc.)',
    status VARCHAR(20) NOT NULL COMMENT 'Current, past, or canceled',
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL if ongoing',
    github_url VARCHAR(255) NULL,
    website_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Project translations (multilingual content)
CREATE TABLE project_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    language_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    description TEXT NOT NULL,
    skills TEXT NOT NULL COMMENT 'Comma-separated list of skills',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (project_id, language_id)
);

-- Personal info (about section)
CREATE TABLE personal_info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    github_url VARCHAR(255) NOT NULL,
    linkedin_url VARCHAR(255) NOT NULL,
    discord_url VARCHAR(255) NULL,
    profile_picture_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Personal info translations
CREATE TABLE personal_info_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_info_id INT NOT NULL,
    language_id INT NOT NULL,
    about_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (personal_info_id) REFERENCES personal_info(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (personal_info_id, language_id)
);

-- Static UI Texts (for multilingual interface elements)
CREATE TABLE ui_texts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_key VARCHAR(100) NOT NULL COMMENT 'Unique identifier for the text',
    context VARCHAR(100) NULL COMMENT 'Where this text is used'
);

-- UI text translations
CREATE TABLE ui_text_translations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ui_text_id INT NOT NULL,
    language_id INT NOT NULL,
    text TEXT NOT NULL,
    FOREIGN KEY (ui_text_id) REFERENCES ui_texts(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id),
    UNIQUE KEY (ui_text_id, language_id)
);

-- Initialize languages
INSERT INTO languages (code, name) VALUES 
('en', 'English'), 
('fr', 'Français');

-- Insert default user (password: Admin@123)
INSERT INTO users (username, email, password_hash, first_name, last_name) 
VALUES ('admin', 'nathan.polette@gmail.com', '$2y$10$q4i2RHJrIFtRY3QZxv5NpuroAQGy3XqHCVbxOEN6JZi3ZpM7z.lMK', 'Nathan', 'Polette');
SQL;
            
            file_put_contents($schemaFile, $schema);
        }
        
        // Execute the schema SQL
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
        
        echo "  Schema created successfully.\n";
        
        // Insert initial translations
        echo "  Adding default translations...\n";
        
        // Define UI texts
        $uiTexts = [
            'site.title' => [
                'en' => 'Polette Nathan - Portfolio',
                'fr' => 'Polette Nathan - Portfolio'
            ],
            'meta.description' => [
                'en' => 'Nathan Polette\'s personal portfolio - Computer Engineering Student',
                'fr' => 'Portfolio personnel de Nathan Polette - Étudiant en ingénierie informatique'
            ],
            'nav.projects' => [
                'en' => 'Projects',
                'fr' => 'Projets'
            ],
            'nav.contact' => [
                'en' => 'Contact',
                'fr' => 'Contact'
            ],
            'nav.admin' => [
                'en' => 'Admin',
                'fr' => 'Admin'
            ],
            'nav.logout' => [
                'en' => 'Logout',
                'fr' => 'Déconnexion'
            ],
            'nav.aria_label' => [
                'en' => 'Main navigation',
                'fr' => 'Navigation principale'
            ],
            'footer.about_title' => [
                'en' => 'About me',
                'fr' => 'Un petit résumé rapide'
            ],
            'footer.contact_title' => [
                'en' => 'Contacts & accounts',
                'fr' => 'Contacts & comptes'
            ],
            'footer.rights_reserved' => [
                'en' => 'All rights reserved. (design & code by Asha Geyon)',
                'fr' => 'Tous droits réservés. (design & code par Asha Geyon)'
            ],
            'warning.title' => [
                'en' => 'Warning',
                'fr' => 'Attention'
            ],
            'warning.portrait_mode' => [
                'en' => 'This website is not optimized for portrait mode, the experience will be degraded. For the best experience, please use a computer.',
                'fr' => 'Ce site n\'est pas optimisé pour l\'affichage en mode portrait, l\'expérience en sera dégradée. Pour une meilleure expérience, veuillez utiliser un ordinateur.'
            ],
            'warning.got_it' => [
                'en' => 'OK, got it',
                'fr' => 'OK, j\'ai compris'
            ],
            'contact.submit' => [
                'en' => 'Send',
                'fr' => 'Envoyer'
            ],
            'contact.subject' => [
                'en' => 'Subject',
                'fr' => 'Sujet'
            ],
            'contact.message' => [
                'en' => 'Message',
                'fr' => 'Message'
            ],
            'contact.success' => [
                'en' => 'Your message has been sent successfully.',
                'fr' => 'Votre message a été envoyé avec succès.'
            ],
            'contact.error.subject_required' => [
                'en' => 'Subject is required.',
                'fr' => 'Le sujet est obligatoire.'
            ],
            'contact.error.message_required' => [
                'en' => 'Message is required.',
                'fr' => 'Le message est obligatoire.'
            ],
            'contact.error.invalid_email' => [
                'en' => 'Please enter a valid email address.',
                'fr' => 'Veuillez entrer une adresse email valide.'
            ],
            'login.title' => [
                'en' => 'Login',
                'fr' => 'Connexion'
            ],
            'login.email' => [
                'en' => 'Email',
                'fr' => 'Email'
            ],
            'login.password' => [
                'en' => 'Password',
                'fr' => 'Mot de passe'
            ],
            'login.remember_me' => [
                'en' => 'Remember me',
                'fr' => 'Se souvenir de moi'
            ],
            'login.submit' => [
                'en' => 'Login',
                'fr' => 'Se connecter'
            ],
            'login.forgot_password' => [
                'en' => 'Forgot password?',
                'fr' => 'Mot de passe oublié ?'
            ],
        ];
        
        // Get language IDs
        $languages = [];
        $stmt = $pdo->query("SELECT id, code FROM languages");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $languages[$row['code']] = $row['id'];
        }
        
        // Insert UI texts
        foreach ($uiTexts as $key => $translations) {
            // Insert key
            $stmt = $pdo->prepare("INSERT INTO ui_texts (text_key) VALUES (?)");
            $stmt->execute([$key]);
            $textId = $pdo->lastInsertId();
            
            // Insert translations
            foreach ($translations as $langCode => $text) {
                if (isset($languages[$langCode])) {
                    $stmt = $pdo->prepare("INSERT INTO ui_text_translations (ui_text_id, language_id, text) VALUES (?, ?, ?)");
                    $stmt->execute([$textId, $languages[$langCode], $text]);
                }
            }
        }
        
        // Insert default personal info
        $stmt = $pdo->prepare("INSERT INTO personal_info (email, github_url, linkedin_url, discord_url, profile_picture_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'nathan.polette@gmail.com',
            'https://github.com/Natpol50',
            'https://fr.linkedin.com/in/polette-nathan',
            'https://discordapp.com/users/1151796352548880406',
            '/assets/img/AshaLogo.png'
        ]);
        $personalInfoId = $pdo->lastInsertId();
        
        // Insert translations for personal info
        $aboutTextEN = "Hi, I'm Nathan Polette,\nbetter known by the username Asha Geyon (Natpol50).\nI've been developing and learning for almost\n6 years now with the goal of becoming\nas versatile as possible.\nHave a challenge or an idea? Feel free to contact me!\n(By the way, I also draw and do design from time to time)";
        
        $aboutTextFR = "Salut, je suis Nathan Polette,\nplus connu sous le pseudonyme Asha Geyon (Natpol50).\nCela fait maintenant presque 6 ans que je\ndéveloppe et j'apprends dans l'optique de\ndevenir le plus polyvalent possible.\nUn défi, une idée ? N'hésitez pas à me contacter !\n(Au fait, je dessine aussi et fais du design de temps en temps)";
        
        $stmt = $pdo->prepare("INSERT INTO personal_info_translations (personal_info_id, language_id, about_text) VALUES (?, ?, ?)");
        $stmt->execute([$personalInfoId, $languages['en'], $aboutTextEN]);
        $stmt->execute([$personalInfoId, $languages['fr'], $aboutTextFR]);
        
        echo "  Initial data inserted successfully.\n";
    } else {
        echo "  Tables already exist. Skipping schema creation.\n";
    }
    
    echo "  Database setup completed successfully.\n";
    
} catch (PDOException $e) {
    echo "  Database error: " . $e->getMessage() . "\n";
}

// Step 4: Create .htaccess file in public directory
echo "\nStep 4: Creating .htaccess file...\n";

$htaccessFile = $rootDir . '/public/.htaccess';

if (!file_exists($htaccessFile)) {
    $htaccessContent = <<<HTACCESS
# Enable rewrite engine
RewriteEngine On

# If the file or directory exists, serve it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Otherwise, redirect to index.php
RewriteRule ^ index.php [QSA,L]

# Set default character set
AddDefaultCharset UTF-8

# Enable gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</IfModule>

# Set caching headers for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
HTACCESS;

    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "  .htaccess file created successfully.\n";
    } else {
        echo "  Failed to create .htaccess file.\n";
    }
} else {
    echo "  .htaccess file already exists.\n";
}

// Copy default logo if it doesn't exist
echo "\nStep 5: Checking for default logo...\n";

$logoPath = $rootDir . '/public/assets/img/AshaLogo.png';

if (!file_exists($logoPath)) {
    // Try to copy from any of the document roots we found in the HTML files
    $possibleSources = [
        '/img/AshaLogo.png',
        'https://cdn.glitch.global/f250f932-9bf3-4789-856d-3d602dd165a4/AshaLogo.PNG?v=1732453904661'
    ];
    
    $logoFound = false;
    
    foreach ($possibleSources as $source) {
        if (strpos($source, 'http') === 0) {
            // Try to download from URL
            $logoContent = @file_get_contents($source);
            if ($logoContent) {
                if (file_put_contents($logoPath, $logoContent)) {
                    echo "  Logo downloaded and saved to $logoPath\n";
                    $logoFound = true;
                    break;
                }
            }
        } else {
            // Try to copy from local path
            $localPath = $rootDir . $source;
            if (file_exists($localPath)) {
                if (copy($localPath, $logoPath)) {
                    echo "  Logo copied from $localPath to $logoPath\n";
                    $logoFound = true;
                    break;
                }
            }
        }
    }
    
    if (!$logoFound) {
        echo "  Could not find or create logo. Please add a logo manually to $logoPath\n";
    }
} else {
    echo "  Logo already exists at $logoPath\n";
}

// Final message
echo "\n";
echo "╔════════════════════════════════════════════╗\n";
echo "║    Installation completed successfully!    ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n";
echo "You can now access your website by navigating to:\n";
echo "http://localhost/\n\n";
echo "Admin credentials:\n";
echo "Email: nathan.polette@gmail.com\n";
echo "Password: Admin@123\n\n";
