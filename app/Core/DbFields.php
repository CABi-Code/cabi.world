<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Единые константы для полей SELECT
 */
class DbFields
{
    // === Users ===
    public const USER_PUBLIC = 'id, login, email, username, bio, avatar, banner, discord, telegram, vk, 
        is_active, role, theme, view_mode, profile_bg_type, profile_bg_value, avatar_bg_type, avatar_bg_value, 
        banner_bg_type, banner_bg_value, created_at';
    
    public const USER_AUTH = 'id, login, email, password_hash, username, is_active, role';
    
    public const USER_SHORT = 'id, login, username, avatar, avatar_bg_type, avatar_bg_value, role';
    
    // === Applications ===
    public const APP_BASE = 'a.id, a.modpack_id, a.user_id, a.message, a.relevant_until, a.char_count,
        a.contact_discord, a.contact_telegram, a.contact_vk, a.status, a.is_hidden, a.created_at, a.updated_at';
    
    public const APP_WITH_MODPACK = self::APP_BASE . ',
        m.name as modpack_name, m.slug, m.platform, m.icon_url, m.accepted_count';
    
    public const APP_WITH_USER = self::APP_BASE . ',
        u.login, u.username, u.avatar, u.avatar_bg_type, u.avatar_bg_value, u.role';
    
    public const APP_FULL = self::APP_BASE . ',
        m.name as modpack_name, m.slug, m.platform, m.icon_url, m.accepted_count,
        u.login, u.username, u.avatar, u.avatar_bg_type, u.avatar_bg_value, u.role';
    
    // === Modpacks ===
    public const MODPACK_BASE = 'id, platform, external_id, slug, name, description, icon_url, author, 
        downloads, follows, accepted_count, external_url, cached_at';
    
    // === Notifications ===
    public const NOTIF_BASE = 'id, user_id, type, title, message, link, is_read, created_at';
}