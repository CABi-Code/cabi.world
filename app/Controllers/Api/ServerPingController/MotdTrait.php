<?php

namespace App\Controllers\Api\ServerPingController;

use App\Http\Request;
use App\Http\Response;
use App\Repository\ServerPingRepository;

trait MotdTrait
{

	/**
	 * Парсинг MOTD из description (поддержка строки и chat component)
	 */
	private function parseMotd($description): array
	{
		$raw = [];
		$clean = [];
		$html = [];

		if (is_string($description)) {
			$raw[] = $description;
			$clean[] = preg_replace('/§[0-9a-fk-or]/i', '', $description);
			$html[] = $this->motdToHtml($description);
		} elseif (is_array($description)) {
			// Chat component format
			$text = $this->flattenChatComponent($description);
			$lines = explode("\n", $text);
			foreach ($lines as $line) {
				$raw[] = $line;
				$clean[] = preg_replace('/§[0-9a-fk-or]/i', '', $line);
				$html[] = $this->motdToHtml($line);
			}
		}

		return [
			'raw'   => $raw,
			'clean' => $clean,
			'html'  => $html,
		];
	}

	/**
	 * Рекурсивно собирает текст из chat component
	 */
	private function flattenChatComponent(array $component): string
	{
		$text = '';

		// Форматирование → §-коды
		if (!empty($component['color'])) {
			$text .= $this->colorToSection($component['color']);
		}
		if (!empty($component['bold'])) $text .= '§l';
		if (!empty($component['italic'])) $text .= '§o';
		if (!empty($component['underlined'])) $text .= '§n';
		if (!empty($component['strikethrough'])) $text .= '§m';
		if (!empty($component['obfuscated'])) $text .= '§k';

		$text .= $component['text'] ?? '';

		if (!empty($component['extra'])) {
			foreach ($component['extra'] as $child) {
				$text .= $this->flattenChatComponent($child);
			}
		}

		return $text;
	}
	
	private function colorToSection(string $color): string
	{
		$map = [
			'black' => '§0', 'dark_blue' => '§1', 'dark_green' => '§2',
			'dark_aqua' => '§3', 'dark_red' => '§4', 'dark_purple' => '§5',
			'gold' => '§6', 'gray' => '§7', 'dark_gray' => '§8',
			'blue' => '§9', 'green' => '§a', 'aqua' => '§b',
			'red' => '§c', 'light_purple' => '§d', 'yellow' => '§e',
			'white' => '§f',
		];
		return $map[$color] ?? '';
	}
	
	/**
	 * Конвертация §-кодов в HTML-спаны
	 */
	private function motdToHtml(string $text): string
	{
		$colorMap = [
			'0' => '#000000', '1' => '#0000AA', '2' => '#00AA00', '3' => '#00AAAA',
			'4' => '#AA0000', '5' => '#AA00AA', '6' => '#FFAA00', '7' => '#AAAAAA',
			'8' => '#555555', '9' => '#5555FF', 'a' => '#55FF55', 'b' => '#55FFFF',
			'c' => '#FF5555', 'd' => '#FF55FF', 'e' => '#FFFF55', 'f' => '#FFFFFF',
		];

		$segments = [];
		$currentText = '';
		$currentColor = null;
		$currentStyles = [];
		$len = mb_strlen($text);
		$i = 0;

		while ($i < $len) {
			$char = mb_substr($text, $i, 1);
			
			if ($char === '§' && $i + 1 < $len) {
				$code = strtolower(mb_substr($text, $i + 1, 1));

				if (isset($colorMap[$code])) {
					// Сохраняем текущий сегмент
					if ($currentText !== '') {
						$segments[] = [
							'text' => $currentText,
							'color' => $currentColor,
							'styles' => $currentStyles
						];
						$currentText = '';
					}
					
					// Меняем цвет
					$currentColor = $colorMap[$code];
					$currentStyles = [];
					
				} elseif ($code === 'l') {
					$currentStyles['bold'] = true;
				} elseif ($code === 'o') {
					$currentStyles['italic'] = true;
				} elseif ($code === 'n') {
					$currentStyles['underline'] = true;
				} elseif ($code === 'm') {
					$currentStyles['strike'] = true;
				} elseif ($code === 'r') {
					// Сохраняем текущий сегмент
					if ($currentText !== '') {
						$segments[] = [
							'text' => $currentText,
							'color' => $currentColor,
							'styles' => $currentStyles
						];
						$currentText = '';
					}
					$currentColor = null;
					$currentStyles = [];
				}
				$i += 2;
				
			} else {
				$currentText .= $char;
				$i++;
			}
		}

		// Сохраняем последний сегмент
		if ($currentText !== '') {
			$segments[] = [
				'text' => $currentText,
				'color' => $currentColor,
				'styles' => $currentStyles
			];
		}

		// Генерируем HTML
		$result = '';
		foreach ($segments as $segment) {
			$styles = [];
			if ($segment['color']) {
				$styles[] = 'color: ' . $segment['color'];
			}
			if (!empty($segment['styles']['bold'])) {
				$styles[] = 'font-weight: bold';
			}
			if (!empty($segment['styles']['italic'])) {
				$styles[] = 'font-style: italic';
			}
			if (!empty($segment['styles']['underline'])) {
				$styles[] = 'text-decoration: underline';
			}
			if (!empty($segment['styles']['strike'])) {
				$styles[] = 'text-decoration: line-through';
			}

			$styleAttr = !empty($styles) ? ' style="' . implode('; ', $styles) . ';"' : '';
			$text = htmlspecialchars($segment['text'], ENT_QUOTES, 'UTF-8');
			
			$result .= '<span' . $styleAttr . '>' . $text . '</span>';
		}

		return $result;
	}
	
}