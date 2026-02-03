<?php

declare(strict_types=1);

namespace App\Http;

class Request
{
    private array $data;
    private array $files;
    private ?array $user = null;
    private string $method;
    private string $uri;
    private array $query;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $this->uri = rtrim($this->uri, '/') ?: '/';
        $this->query = $_GET ?? [];
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $this->data = json_decode(file_get_contents('php://input'), true) ?? [];
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $this->data = $_POST;
        } else {
            $this->data = array_merge($_POST, $_GET);
        }
        
        $this->files = $_FILES ?? [];
    }

    public function all(): array
    {
        return $this->data;
    }

	/**
	 * Возвращает только указанные поля из данных запроса
	 * 
	 * @param array $keys Список ключей, которые нужно получить
	 * @return array
	 */
	public function only(array $keys): array
	{
		$result = [];
		foreach ($keys as $key) {
			if (array_key_exists($key, $this->data)) {
				$result[$key] = $this->data[$key];
			}
		}
		return $result;
	}

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function hasFile(string $key): bool
    {
        if (!isset($this->files[$key])) {
            return false;
        }
        
        $file = $this->files[$key];
        if (is_array($file['error'] ?? null)) {
            return isset($file['error'][0]) && $file['error'][0] === UPLOAD_ERR_OK;
        }
        
        return isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function files(string $key): array
    {
        if (!isset($this->files[$key])) {
            return [];
        }
        
        $files = $this->files[$key];
        
        // Если один файл
        if (isset($files['name']) && !is_array($files['name'])) {
            return [$files];
        }
        
        // Если несколько файлов
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }
        
        $result = [];
        foreach ($files['name'] as $i => $name) {
            $result[] = [
                'name' => $name,
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
        return $result;
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function setUser(array $user): void
    {
        $this->user = $user;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    public function isFormData(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return strpos($contentType, 'multipart/form-data') !== false;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
}
