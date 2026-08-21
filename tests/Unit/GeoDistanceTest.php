<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\GeoDistance;
use PHPUnit\Framework\TestCase;

/**
 * Titik-titik uji di sini sengaja dipilih yang jaraknya bisa dihitung pasti
 * secara matematis (bukan "jarak kota A ke kota B kira-kira sekian km" yang
 * rawan salah ingat), supaya nilai pembandingnya benar-benar bisa
 * dipertanggungjawabkan:
 * - Titik yang sama -> jarak 0
 * - 1 derajat di ekuator -> R * radian(1 derajat)
 * - Kutub utara dari ekuator -> seperempat keliling bumi
 * - Titik antipodal (sisi berlawanan bumi) -> setengah keliling bumi
 */
class GeoDistanceTest extends TestCase
{
    private const EARTH_RADIUS_METERS = 6_371_000;

    public function test_distance_between_identical_points_is_zero(): void
    {
        $distance = GeoDistance::metersBetween(-8.219, 114.369, -8.219, 114.369);

        $this->assertEqualsWithDelta(0.0, $distance, 0.001);
    }

    public function test_distance_for_one_degree_longitude_at_equator(): void
    {
        $expected = self::EARTH_RADIUS_METERS * deg2rad(1);

        $distance = GeoDistance::metersBetween(0, 0, 0, 1);

        $this->assertEqualsWithDelta($expected, $distance, 1.0);
    }

    public function test_distance_for_one_degree_latitude_matches_longitude_at_equator(): void
    {
        $expected = self::EARTH_RADIUS_METERS * deg2rad(1);

        $distance = GeoDistance::metersBetween(0, 0, 1, 0);

        $this->assertEqualsWithDelta($expected, $distance, 1.0);
    }

    public function test_distance_from_equator_to_north_pole_is_quarter_circumference(): void
    {
        $expected = (M_PI / 2) * self::EARTH_RADIUS_METERS;

        $distance = GeoDistance::metersBetween(0, 0, 90, 0);

        $this->assertEqualsWithDelta($expected, $distance, 10.0);
    }

    public function test_distance_between_antipodal_points_is_half_circumference(): void
    {
        $expected = M_PI * self::EARTH_RADIUS_METERS;

        $distance = GeoDistance::metersBetween(0, 0, 0, 180);

        $this->assertEqualsWithDelta($expected, $distance, 10.0);
    }

    public function test_short_distance_matches_onsite_radius_scale(): void
    {
        // Dua titik ~111 meter terpisah (0.001 derajat lintang di ekuator)
        // -- skala jarak yang relevan untuk verifikasi radius RSUD
        // (ratusan meter, bukan kilometer).
        $expected = self::EARTH_RADIUS_METERS * deg2rad(0.001);

        $distance = GeoDistance::metersBetween(0, 0, 0.001, 0);

        $this->assertEqualsWithDelta($expected, $distance, 0.5);
        $this->assertGreaterThan(100.0, $distance);
        $this->assertLessThan(120.0, $distance);
    }
}
