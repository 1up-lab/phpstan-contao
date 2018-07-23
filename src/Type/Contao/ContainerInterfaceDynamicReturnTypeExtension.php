<?php

declare(strict_types=1);

namespace PHPStan\Type\Contao;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Contao\ServiceMap;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerInterfaceDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var ServiceMap
     */
    private $serviceMap;

    public function __construct(ServiceMap $contaoServiceMap)
    {
        $this->serviceMap = $contaoServiceMap;
    }

    public function getClass(): string
    {
        return ContainerInterface::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'get' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        if (0 === \count($methodCall->args)) {
            return \PHPStan\Reflection\ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $methodCall->args,
                $methodReflection->getVariants()
            )->getReturnType();
        }

        $serviceId = ServiceMap::getServiceIdFromNode($methodCall->args[0]->value, $scope);

        if (null !== $serviceId && null !== $service = $this->serviceMap->getService($serviceId)) {
            return new ObjectType($service->getClass());
        }

        $arg = $methodCall->args[0]->value;

        return $scope->getType($arg);
    }
}
