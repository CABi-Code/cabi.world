--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `application_folder_links`
--
ALTER TABLE `application_folder_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_afl_link` (`application_id`,`folder_item_id`),
  ADD KEY `idx_afl_folder` (`folder_item_id`);

--
-- Индексы таблицы `application_images`
--
ALTER TABLE `application_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_images_app` (`application_id`);

--
-- Индексы таблицы `auth_logs`
--
ALTER TABLE `auth_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_auth_logs_user` (`user_id`),
  ADD KEY `idx_auth_logs_created` (`created_at`),
  ADD KEY `idx_auth_logs_ip` (`ip_address`);

--
-- Индексы таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_chat` (`chat_id`),
  ADD KEY `idx_messages_user` (`user_id`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Индексы таблицы `chat_message_images`
--
ALTER TABLE `chat_message_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_message_images` (`message_id`);

--
-- Индексы таблицы `chat_message_likes`
--
ALTER TABLE `chat_message_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_message_like` (`message_id`,`user_id`),
  ADD KEY `idx_likes_user` (`user_id`);

--
-- Индексы таблицы `chat_polls`
--
ALTER TABLE `chat_polls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `message_id` (`message_id`),
  ADD KEY `idx_polls_message` (`message_id`);

--
-- Индексы таблицы `chat_poll_options`
--
ALTER TABLE `chat_poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_options_poll` (`poll_id`);

--
-- Индексы таблицы `chat_poll_votes`
--
ALTER TABLE `chat_poll_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_poll_vote` (`option_id`,`user_id`),
  ADD KEY `idx_votes_user` (`user_id`);

--
-- Индексы таблицы `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_communities_user` (`user_id`);

--
-- Индексы таблицы `community_bans`
--
ALTER TABLE `community_bans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ban_scope` (`community_id`,`user_id`,`scope`,`scope_id`),
  ADD KEY `idx_bans_user` (`user_id`),
  ADD KEY `idx_bans_expires` (`expires_at`),
  ADD KEY `fk_bans_banned_by` (`banned_by`);

--
-- Индексы таблицы `community_chats`
--
ALTER TABLE `community_chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chats_community` (`community_id`),
  ADD KEY `idx_chats_folder` (`folder_id`),
  ADD KEY `idx_chats_last_message` (`last_message_at`);

--
-- Индексы таблицы `community_folders`
--
ALTER TABLE `community_folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_folders_community` (`community_id`),
  ADD KEY `idx_folders_parent` (`parent_id`);

--
-- Индексы таблицы `community_moderators`
--
ALTER TABLE `community_moderators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_moderator_scope` (`community_id`,`user_id`,`scope`,`scope_id`),
  ADD KEY `idx_moderators_user` (`user_id`);

--
-- Индексы таблицы `community_subscribers`
--
ALTER TABLE `community_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_community_subscriber` (`community_id`,`user_id`),
  ADD KEY `idx_subscribers_user` (`user_id`);

--
-- Индексы таблицы `folder_paths`
--
ALTER TABLE `folder_paths`
  ADD PRIMARY KEY (`ancestor_id`,`descendant_id`),
  ADD KEY `idx_fp_descendant` (`descendant_id`),
  ADD KEY `idx_fp_depth` (`depth`);

--
-- Индексы таблицы `modpacks`
--
ALTER TABLE `modpacks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_modpacks_platform_external` (`platform`,`external_id`),
  ADD KEY `idx_modpacks_slug` (`slug`),
  ADD KEY `idx_modpacks_platform` (`platform`);

--
-- Индексы таблицы `modpack_applications`
--
ALTER TABLE `modpack_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applications_modpack` (`modpack_id`),
  ADD KEY `idx_applications_user` (`user_id`),
  ADD KEY `idx_applications_status` (`status`),
  ADD KEY `idx_app_visible` (`status`,`is_hidden`);

--
-- Индексы таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`),
  ADD KEY `idx_notifications_read` (`user_id`,`is_read`);

--
-- Индексы таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_resets_token` (`token_hash`),
  ADD KEY `idx_password_resets_user` (`user_id`);

--
-- Индексы таблицы `rate_limit_blocks`
--
ALTER TABLE `rate_limit_blocks`
  ADD PRIMARY KEY (`identifier`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_captcha` (`captcha_solved`,`expires_at`);

--
-- Индексы таблицы `rate_limit_counters`
--
ALTER TABLE `rate_limit_counters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_key_created` (`key_name`,`created_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Индексы таблицы `rate_limit_violations`
--
ALTER TABLE `rate_limit_violations`
  ADD PRIMARY KEY (`identifier`),
  ADD KEY `idx_updated` (`updated_at`);

--
-- Индексы таблицы `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_refresh_tokens_hash` (`token_hash`),
  ADD KEY `idx_refresh_tokens_user` (`user_id`),
  ADD KEY `idx_refresh_tokens_expires` (`expires_at`),
  ADD KEY `idx_refresh_tokens_fingerprint` (`fingerprint`);

--
-- Индексы таблицы `server_ping_history`
--
ALTER TABLE `server_ping_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sph_item` (`item_id`),
  ADD KEY `idx_sph_time` (`checked_at`),
  ADD KEY `idx_sph_item_time` (`item_id`,`checked_at`),
  ADD KEY `idx_sph_cleanup` (`checked_at`);

--
-- Индексы таблицы `site_stats`
--
ALTER TABLE `site_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_key` (`stat_key`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_login` (`login`),
  ADD UNIQUE KEY `uk_users_email` (`email`),
  ADD KEY `idx_users_is_active` (`is_active`),
  ADD KEY `idx_users_role` (`role`);

--
-- Индексы таблицы `user_folder_items`
--
ALTER TABLE `user_folder_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ufi_user` (`user_id`),
  ADD KEY `idx_ufi_parent` (`parent_id`),
  ADD KEY `idx_ufi_type` (`item_type`),
  ADD KEY `idx_ufi_category` (`folder_category`),
  ADD KEY `idx_ufi_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_ufi_sort` (`user_id`,`parent_id`,`sort_order`),
  ADD KEY `idx_ufi_user_type` (`user_id`,`item_type`),
  ADD KEY `idx_ufi_hidden` (`is_hidden`);

--
-- Индексы таблицы `user_folder_subscriptions`
--
ALTER TABLE `user_folder_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ufs_subscription` (`folder_owner_id`,`subscriber_id`),
  ADD KEY `idx_ufs_subscriber` (`subscriber_id`);

--
-- Индексы таблицы `user_servers`
--
ALTER TABLE `user_servers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_us_user` (`user_id`);

--
-- Индексы таблицы `user_shortcuts`
--
ALTER TABLE `user_shortcuts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ush_user` (`user_id`);