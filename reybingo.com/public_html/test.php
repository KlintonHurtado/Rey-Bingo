require 'app/Config/Database.php'; \ = \Config\Database::connect(); \ = \->query('SELECT * FROM cartons LIMIT 5;'); print_r(\->getResultArray());
