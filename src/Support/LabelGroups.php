<?php

namespace InternetGuru\LaravelCommon\Support;

use Closure;

/**
 * The sets labels are colored from. A group is a list of keys registered once,
 * or an enum class using RendersLabel; each key takes the palette color of its
 * place in the list, so a value has one color everywhere and no two values of
 * a group share one.
 *
 * Registrations are kept for the life of the process, while the colors they
 * resolve to are kept for one request only: the container binds this class as
 * scoped, so a group read from the database is read again on the next one.
 */
class LabelGroups
{
    /**
     * @var array<string, array{keys: iterable<int|string>|Closure(): iterable<int|string>, forced: array<int|string, int>, seed: int|string|null}>
     */
    protected static array $groups = [];

    /** @var array<string, array<int|string, string>> */
    protected array $colors = [];

    /**
     * Register a group, or replace one of the same name. Keys given as a
     * closure are read when the group is first used in a request.
     *
     * @param  iterable<int|string>|Closure(): iterable<int|string>  $keys  The keys in a stable order.
     * @param  array<int|string, int>  $forced  Slots given to some keys by hand.
     * @param  int|string|null  $seed  What turns the palette; the name of the group by default.
     */
    public static function register(string $name, iterable|Closure $keys, array $forced = [], int|string|null $seed = null): void
    {
        static::$groups[$name] = ['keys' => $keys, 'forced' => $forced, 'seed' => $seed];
        unset(app(self::class)->colors[$name]);
    }

    /**
     * The color of every key of a group, or null for a group that is neither
     * registered nor an enum using RendersLabel.
     *
     * @return array<int|string, string>|null
     */
    public static function colors(string $group): ?array
    {
        return app(self::class)->resolve($group);
    }

    /** Forget every registration, for tests. */
    public static function flush(): void
    {
        static::$groups = [];
        app(self::class)->colors = [];
    }

    /** @return array<int|string, string>|null */
    protected function resolve(string $group): ?array
    {
        if (isset($this->colors[$group])) {
            return $this->colors[$group];
        }

        if (isset(static::$groups[$group])) {
            ['keys' => $keys, 'forced' => $forced, 'seed' => $seed] = static::$groups[$group];
            $keys = $keys instanceof Closure ? $keys() : $keys;
            $keys = is_array($keys) ? array_values($keys) : iterator_to_array($keys, false);

            return $this->colors[$group] = Palette::colors($keys, $forced, $seed ?? $group);
        }

        if (enum_exists($group) && method_exists($group, 'labelColors')) {
            return $this->colors[$group] = $group::labelColors();
        }

        return null;
    }
}
