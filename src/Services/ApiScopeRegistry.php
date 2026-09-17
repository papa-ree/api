<?php

namespace Bale\Api\Services;

use InvalidArgumentException;

/**
 * Registry scope API.
 *
 * Setiap package mendaftarkan scope-nya sendiri lewat helper
 * {@see registerApiScopes()} (lihat src/helpers.php) sehingga `bale/api`
 * tidak perlu mengetahui domain package lain.
 */
class ApiScopeRegistry
{
    /**
     * @var array<string, array<string, string>> group => [scope => description]
     */
    protected array $groups = [];

    /**
     * Daftarkan scope untuk sebuah group/domain.
     *
     * Menerima dua bentuk:
     *   ['rakaca.form.read' => 'Deskripsi', ...]
     *   ['rakaca.form.read', 'rakaca.submission.read']  (deskripsi kosong)
     *
     * @param  array<int|string, string>  $scopes
     */
    public function register(string $group, array $scopes): void
    {
        $group = trim($group);

        if ($group === '') {
            throw new InvalidArgumentException('Scope group name cannot be empty.');
        }

        if (! isset($this->groups[$group])) {
            $this->groups[$group] = [];
        }

        foreach ($scopes as $key => $value) {
            $name = is_int($key) ? trim($value) : trim((string) $key);
            $description = is_int($key) ? '' : trim((string) $value);

            if ($name === '') {
                continue;
            }

            $this->groups[$group][$name] = $description;
        }
    }

    /**
     * Nama scope per group — dipakai untuk checkbox pada UI token.
     *
     * @return array<string, list<string>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->groups as $group => $scopes) {
            $grouped[$group] = array_keys($scopes);
        }

        return $grouped;
    }

    /**
     * Scope lengkap dengan deskripsi — dipakai katalog referensi API.
     *
     * @return array<string, list<array{name: string, description: string}>>
     */
    public function scopes(): array
    {
        $scopes = [];

        foreach ($this->groups as $group => $items) {
            foreach ($items as $name => $description) {
                $scopes[$group][] = [
                    'name' => $name,
                    'description' => $description,
                ];
            }
        }

        return $scopes;
    }

    /**
     * Semua nama scope dalam satu daftar datar.
     *
     * @return list<string>
     */
    public function flat(): array
    {
        $flat = [];

        foreach ($this->groups as $items) {
            foreach (array_keys($items) as $name) {
                $flat[] = $name;
            }
        }

        return array_values(array_unique($flat));
    }

    public function has(string $scope): bool
    {
        return in_array($scope, $this->flat(), true);
    }

    public function description(string $scope): ?string
    {
        foreach ($this->groups as $items) {
            if (array_key_exists($scope, $items)) {
                return $items[$scope];
            }
        }

        return null;
    }
}
