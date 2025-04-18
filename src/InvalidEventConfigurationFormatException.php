<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

/**
 * @final
 */
class InvalidEventConfigurationFormatException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Configuration format passed to EventConfigurator is invalid.';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            EventConfigurator accepts an array in the following format:
                [
                    'eventName1' => [\$listener1, \$listener2, ...],
                    'eventName2' => [\$listener3, \$listener4, ...],
                    ...
                ]
        SOLUTION;
    }
}
