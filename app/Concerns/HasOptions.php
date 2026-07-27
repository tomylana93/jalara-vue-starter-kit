<?php

namespace App\Concerns;

use LogicException;

trait HasOptions
{
    /**
     * Get the enum cases as options.
     *
     * @param  array<string, string>  $additionalFields
     * @return list<array<string, int|string>>
     */
    public static function options(array $additionalFields = []): array
    {
        return array_map(
            function (self $case) use ($additionalFields): array {
                $option = [
                    'label' => $case->label(),
                    'value' => $case->value,
                ];

                foreach ($additionalFields as $field => $method) {
                    if (! method_exists($case, $method)) {
                        throw new LogicException(sprintf(
                            'Method [%s] does not exist on enum [%s].',
                            $method,
                            $case::class,
                        ));
                    }

                    $option[$field] = $case->{$method}();
                }

                return $option;
            },
            self::cases(),
        );
    }
}
