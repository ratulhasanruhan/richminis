-- State-wise shipping: delivery charge per state (run once; ignore error if column already exists)
ALTER TABLE `states` ADD COLUMN `cost` DOUBLE(20,2) NOT NULL DEFAULT 0;
