--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `application_folder_links`
--
ALTER TABLE `application_folder_links`
  ADD CONSTRAINT `fk_afl_app` FOREIGN KEY (`application_id`) REFERENCES `modpack_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_afl_folder` FOREIGN KEY (`folder_item_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `application_images`
--
ALTER TABLE `application_images`
  ADD CONSTRAINT `fk_app_images_app` FOREIGN KEY (`application_id`) REFERENCES `modpack_applications` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `auth_logs`
--
ALTER TABLE `auth_logs`
  ADD CONSTRAINT `fk_auth_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `fk_messages_chat` FOREIGN KEY (`chat_id`) REFERENCES `community_chats` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_message_images`
--
ALTER TABLE `chat_message_images`
  ADD CONSTRAINT `fk_message_images` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_message_likes`
--
ALTER TABLE `chat_message_likes`
  ADD CONSTRAINT `fk_likes_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_polls`
--
ALTER TABLE `chat_polls`
  ADD CONSTRAINT `fk_polls_message` FOREIGN KEY (`message_id`) REFERENCES `chat_messages` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_poll_options`
--
ALTER TABLE `chat_poll_options`
  ADD CONSTRAINT `fk_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `chat_polls` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `chat_poll_votes`
--
ALTER TABLE `chat_poll_votes`
  ADD CONSTRAINT `fk_votes_option` FOREIGN KEY (`option_id`) REFERENCES `chat_poll_options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_votes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `fk_communities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `community_bans`
--
ALTER TABLE `community_bans`
  ADD CONSTRAINT `fk_bans_banned_by` FOREIGN KEY (`banned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bans_community` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bans_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `community_chats`
--
ALTER TABLE `community_chats`
  ADD CONSTRAINT `fk_chats_community` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_chats_folder` FOREIGN KEY (`folder_id`) REFERENCES `community_folders` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `community_folders`
--
ALTER TABLE `community_folders`
  ADD CONSTRAINT `fk_folders_community` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_folders_parent` FOREIGN KEY (`parent_id`) REFERENCES `community_folders` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `community_moderators`
--
ALTER TABLE `community_moderators`
  ADD CONSTRAINT `fk_moderators_community` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_moderators_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `community_subscribers`
--
ALTER TABLE `community_subscribers`
  ADD CONSTRAINT `fk_subscribers_community` FOREIGN KEY (`community_id`) REFERENCES `communities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subscribers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `folder_paths`
--
ALTER TABLE `folder_paths`
  ADD CONSTRAINT `fk_fp_ancestor` FOREIGN KEY (`ancestor_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fp_descendant` FOREIGN KEY (`descendant_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `modpack_applications`
--
ALTER TABLE `modpack_applications`
  ADD CONSTRAINT `fk_applications_modpack` FOREIGN KEY (`modpack_id`) REFERENCES `modpacks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD CONSTRAINT `fk_refresh_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `server_ping_history`
--
ALTER TABLE `server_ping_history`
  ADD CONSTRAINT `fk_sph_item` FOREIGN KEY (`item_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_folder_items`
--
ALTER TABLE `user_folder_items`
  ADD CONSTRAINT `fk_ufi_parent` FOREIGN KEY (`parent_id`) REFERENCES `user_folder_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ufi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_folder_subscriptions`
--
ALTER TABLE `user_folder_subscriptions`
  ADD CONSTRAINT `fk_ufs_owner` FOREIGN KEY (`folder_owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ufs_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_servers`
--
ALTER TABLE `user_servers`
  ADD CONSTRAINT `fk_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `user_shortcuts`
--
ALTER TABLE `user_shortcuts`
  ADD CONSTRAINT `fk_ush_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;