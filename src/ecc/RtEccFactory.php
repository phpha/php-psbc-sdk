<?php

namespace Psbc\ecc;

use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\Math\GmpMathInterface;
use Mdanter\Ecc\Math\MathAdapterFactory;
use RuntimeException;

// use Psbc\sm\ecc\NistCurve;

/**
 * 添加sm2的工厂，采用类的继承方式，这里也可以采用如 \Curves\CurveFctory的覆盖的方式
 */
class RtEccFactory extends EccFactory
{

    /**
     * Returns a factory to create NIST Recommended curves and generators.
     *
     * @param GmpMathInterface $adapter [optional] Defaults to the return value of EccFactory::getAdapter().
     * @return Sm2Curve
     */
    public static function getSmCurves(GmpMathInterface $adapter = null): Sm2Curve
    {
        $adapter = $adapter ?: self::getAdapter();
        // var_dump($adapter);
        return new Sm2Curve($adapter);
    }

    /**
     * Selects and creates the most appropriate adapter for the running environment.
     *
     * @param bool $debug [optional] Set to true to get a trace of all mathematical operations
     *
     * @return GmpMathInterface
     * @throws RuntimeException
     */
    public static function getAdapter(bool $debug = false): GmpMathInterface
    {

        $adapter = MathAdapterFactory::getAdapter($debug);
        return $adapter;
    }


}
