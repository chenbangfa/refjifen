-- Database Migration for Next Day Points Settlement
-- Please execute this in your database admin tool (e.g. phpMyAdmin, Workbench)

ALTER TABLE orders 
ADD COLUMN points_status tinyint(1) DEFAULT 0 COMMENT '0=Pending, 1=Settled' AFTER status;

ALTER TABLE orders 
ADD COLUMN settle_date date DEFAULT NULL COMMENT 'Date to settle points' AFTER points_status;

ALTER TABLE orders 
ADD COLUMN pending_points decimal(20,4) DEFAULT 0.0000 COMMENT 'Points pending settlement' AFTER settle_date;
