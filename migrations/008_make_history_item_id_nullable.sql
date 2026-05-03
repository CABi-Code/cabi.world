-- Migration 008: make server_ping_history.item_id nullable
-- History is now keyed by server_id (global_servers). item_id is optional
-- and may be absent for pings that aren't tied to a specific folder item.

ALTER TABLE `server_ping_history`
  MODIFY COLUMN `item_id` int(10) UNSIGNED DEFAULT NULL;
