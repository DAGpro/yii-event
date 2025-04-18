<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Event\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Event\InvalidEventConfigurationFormatException;
use Yiisoft\Yii\Event\InvalidListenerConfigurationException;
use Yiisoft\Yii\Event\ListenerConfigurationChecker;
use Yiisoft\Yii\Event\CallableFactory;
use Yiisoft\Yii\Event\Tests\Mock\BadInvokableClass;
use Yiisoft\Yii\Event\Tests\Mock\Event;
use Yiisoft\Yii\Event\Tests\Mock\ExceptionalContainer;
use Yiisoft\Yii\Event\Tests\Mock\HandlerInvokable;
use Yiisoft\Yii\Event\Tests\Mock\Handler;
use Yiisoft\Yii\Event\Tests\Mock\TestClass;

class ListenerConfigurationCheckerTest extends TestCase
{
    public static function badCallableProvider(): array
    {
        return [
            'non-existent container definition' => [
                ['test', 'register'],
                'Listener must be a callable. Got array',
            ],
            'non-existent method' => [
                [Event::class, 'nonExistentMethod'],
                'Could not instantiate "' . Event::class . '" or "nonExistentMethod" method is not defined in this class.',
            ],
            'non-existent method in object' => [
                [new Event(), 'nonExistentMethod'],
                '"nonExistentMethod" method is not defined in "' . Event::class . '" class.',
            ],
            'non-invokable object' => [
                new stdClass(),
                'Listener must be a callable. Got stdClass',
            ],
            'regular array' => [
                [1, 2],
                'Listener must be a callable. Got array',
            ],
            'class not in container' => [
                [Handler::class, 'handle'],
                'Could not instantiate "' . Handler::class . '" or "handle" method is not defined in this class.',
            ],
            'class method null' => [
                [Event::class, null],
                'Listener must be a callable. Got array',
            ],
            'class method integer' => [
                [Event::class, 42],
                'Listener must be a callable. Got array',
            ],
            'int' => [
                ['int', 'handle'],
                'Listener must be a callable. Got array',
            ],
            'string' => [
                ['string', 'handle'],
                'Listener must be a callable. Got array',
            ],
            'string class without invoke' => [
                TestClass::class,
                '"__invoke" method is not defined in "' . TestClass::class . '" class.',
            ],
            'string class' => [
                BadInvokableClass::class,
                'Failed to instantiate "' . BadInvokableClass::class . '" class.',
            ],
        ];
    }

    #[DataProvider('badCallableProvider')]
    public function testBadCallable($callable, string $message): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode(0);

        $this->createChecker()->check([Event::class => [$callable]]);
    }

    public static function goodCallableProvider(): array
    {
        return [
            'array callable' => [[Event::class, 'register']],
            'array callable static' => [Handler::handleStatic(...)],
            'array callable with object' => [[new Event(), 'register']],
            'invokable object' => [new HandlerInvokable()],
            'invokable object to instantiate' => [HandlerInvokable::class],
            'closure' => [
                static function () {
                },
            ],
            'short closure' => [static fn () => null],
        ];
    }

    #[DataProvider('goodCallableProvider')]
    public function testGoodCallable($callable): void
    {
        $checker = $this->createChecker();
        $checker->check([Event::class => [$callable]]);

        $this->assertInstanceOf(ListenerConfigurationChecker::class, $checker);
    }

    public function testExceptionOnHandlerInstantiation(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('Could not instantiate event listener or listener class has invalid configuration. Got array');
        $this->expectExceptionCode(0);

        $callable = [Event::class, 'register'];
        $this->createChecker(new ExceptionalContainer())->check([Event::class => [$callable]]);
    }

    public function testListenersNotIterable(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage(sprintf('Event listeners for %s must be an iterable, stdClass given.', Event::class));
        $this->expectExceptionCode(0);

        $this->createChecker()->check([Event::class => new stdClass()]);
    }

    public function testListenersIncorrectFormat(): void
    {
        $this->expectException(InvalidEventConfigurationFormatException::class);
        $this->expectExceptionMessage('Incorrect event listener format. Format with event name must be used. Got 1.');
        $this->expectExceptionCode(0);

        $this->createChecker()->check([1 => [Event::class, 'register']]);
    }

    private function createChecker(?ContainerInterface $container = null): ListenerConfigurationChecker
    {
        return new ListenerConfigurationChecker(
            new CallableFactory(
                $container ?? new SimpleContainer([
                    Event::class => new Event(),
                    TestClass::class => new TestClass(),
                    HandlerInvokable::class => new HandlerInvokable(),
                    'int' => 7,
                    'string' => 'test',
                ])
            )
        );
    }
}
