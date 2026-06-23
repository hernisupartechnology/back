<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: ProductoFactory
 *
 * Genera productos realistas para el catálogo de pruebas del MVP.
 * La lógica de precios y stock está diseñada para cubrir los
 * tres escenarios de prueba clave del sistema:
 *
 * Escenario A (~50%): Stock saludable, no se activa ninguna alerta.
 * Escenario B (~25%): Stock entre 1 y stock_minimo (alerta activa).
 * Escenario C (~25%): Stock en 0 (alerta crítica, producto agotado).
 *
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Modelo asociado a esta factory.
     */
    protected $model = Producto::class;

    /**
     * Catálogo de productos de ferretería/distribuidora realistas.
     * Cada entrada es un arreglo [nombre, descripción, categoría].
     *
     * @var array<int, array{nombre: string, descripcion: string}>
     */
    private array $catalogoProductos = [
        ['nombre' => 'Taladro Percutor 750W',          'descripcion' => 'Taladro eléctrico percutor 750W con velocidad variable y reversa.'],
        ['nombre' => 'Pintura Blanca Látex 4L',         'descripcion' => 'Pintura de caucho para interiores, acabado mate, rendimiento 12 m²/L.'],
        ['nombre' => 'Cable THW Cal. 12 (rollo 100m)',  'descripcion' => 'Cable eléctrico unipolar THW calibre 12 AWG, 100 metros.'],
        ['nombre' => 'Cemento Gris x 50Kg',             'descripcion' => 'Cemento Portland tipo I, bolsa 50 kilogramos.'],
        ['nombre' => 'Llave de Paso 1/2" PVC',          'descripcion' => 'Llave de paso esférica PVC 1/2 pulgada para instalaciones sanitarias.'],
        ['nombre' => 'Varilla Corrugada 3/8" x 6m',     'descripcion' => 'Varilla de acero corrugado para refuerzo estructural, 6 metros.'],
        ['nombre' => 'Bombillo LED 9W E27',              'descripcion' => 'Bombillo LED 9W base E27, luz blanca 6500K, 800 lúmenes, vida útil 25,000h.'],
        ['nombre' => 'Tubería PVC Presión 1/2" x 6m',   'descripcion' => 'Tubo PVC presión 1/2 pulgada, longitud 6 metros, resistente UV.'],
        ['nombre' => 'Disco de Corte Inox 4.5"',        'descripcion' => 'Disco abrasivo para corte de acero inoxidable, 4.5 pulgadas.'],
        ['nombre' => 'Broca Concreto SDS 10mm',         'descripcion' => 'Broca SDS-plus para concreto y mampostería, diámetro 10mm.'],
        ['nombre' => 'Cinta Métrica 5m Standar',        'descripcion' => 'Cinta métrica de acero, 5 metros, carcasa ABS con freno de bloqueo.'],
        ['nombre' => 'Sellador Acrílico Blanco 300ml',  'descripcion' => 'Sellador acrílico para juntas interiores/exteriores, pistola de silicona.'],
        ['nombre' => 'Interruptor Sencillo 10A',        'descripcion' => 'Interruptor de pared sencillo 10A/250V, compatible con sistema modular.'],
        ['nombre' => 'Codos PVC 90° 1/2"',             'descripcion' => 'Codo PVC de 90 grados para tubería de 1/2 pulgada, pack x10.'],
        ['nombre' => 'Impermeabilizante Acrílico 3.8L', 'descripcion' => 'Impermeabilizante líquido para terrazas y techos, color transparente.'],
        ['nombre' => 'Pulidora Angular 4.5" 850W',      'descripcion' => 'Pulidora angular de 4.5 pulgadas, 850W, 11,000 RPM.'],
        ['nombre' => 'Alambre de Amarre Cal. 16 x 1Kg', 'descripcion' => 'Alambre galvanizado calibre 16 para amarre de varilla en estructuras.'],
        ['nombre' => 'Toma Corriente Doble con Tierra', 'descripcion' => 'Tomacorriente doble polarizado con toma a tierra 15A/125V.'],
        ['nombre' => 'Puntilla 2.5" Caja 1Kg',         'descripcion' => 'Puntillas de acero brillante 2.5 pulgadas, presentación caja 1 kilogramo.'],
        ['nombre' => 'Barniz Brillante Madera 1L',      'descripcion' => 'Barniz poliuretánico de alto brillo para maderas, secado 2 horas.'],
    ];

    /**
     * Define el estado por defecto de un producto generado.
     * La lógica de stock se distribuye en tres escenarios de prueba.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Seleccionar un producto aleatorio del catálogo predefinido.
        $item = $this->faker->randomElement($this->catalogoProductos);

        // -----------------------------------------------------------
        // Lógica de precios: el precio_venta siempre supera al costo
        // con un margen de entre 30% y 40% para asegurar utilidad positiva.
        // Se usa bcmath para precisión monetaria exacta.
        // -----------------------------------------------------------
        $precioCosto  = $this->faker->randomFloat(2, 5000, 150000);
        $margen       = $this->faker->randomFloat(2, 1.30, 1.40);
        $precioVenta  = round($precioCosto * $margen, 2);

        // -----------------------------------------------------------
        // Lógica de stock: distribución en 3 escenarios de prueba
        // para que el endpoint de alertas tenga datos reales qué mostrar.
        //
        // peso 1 (50%) → Stock saludable  → sin alerta
        // peso 2 (25%) → Stock bajo       → alerta activa
        // peso 3 (25%) → Stock agotado    → alerta crítica
        // -----------------------------------------------------------
        $stockMinimo = $this->faker->numberBetween(3, 10);
        $escenario   = $this->faker->randomElement([
            'saludable', 'saludable',  // 50% — stock >= stock_minimo + 1
            'bajo',                    // 25% — stock entre 1 y stock_minimo
            'agotado',                 // 25% — stock = 0
        ]);

        $stockActual = match ($escenario) {
            // Stock suficientemente alto para no disparar alertas.
            'saludable' => $this->faker->numberBetween($stockMinimo + 1, $stockMinimo + 50),
            // Stock en zona de alerta: mayor que 0 pero <= stock_minimo.
            'bajo'      => $this->faker->numberBetween(1, $stockMinimo),
            // Stock agotado: alerta crítica para el administrador.
            'agotado'   => 0,
        };

        // Generar un SKU único con formato legible para el equipo de pruebas.
        // Ejemplo: PRD-A3F9-2B
        $sku = 'PRD-' . strtoupper($this->faker->bothify('####-??'));

        return [
            'sku'          => $sku,
            'nombre'       => $item['nombre'],
            'descripcion'  => $item['descripcion'],
            'precio_costo' => $precioCosto,
            'precio_venta' => $precioVenta,
            'stock_actual' => $stockActual,
            'stock_minimo' => $stockMinimo,
        ];
    }

    // -------------------------------------------------------
    // Estados adicionales para tests unitarios específicos
    // -------------------------------------------------------

    /**
     * Estado: producto con stock garantizado por encima del mínimo.
     * Útil para tests de venta donde se necesita stock disponible.
     */
    public function conStock(int $cantidad = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_actual' => $cantidad,
            'stock_minimo' => 5,
        ]);
    }

    /**
     * Estado: producto sin stock (agotado).
     * Útil para verificar que el VentaService rechaza la venta correctamente.
     */
    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_actual' => 0,
            'stock_minimo' => 5,
        ]);
    }

    /**
     * Estado: producto en zona de alerta (stock <= stock_minimo).
     * Útil para verificar el endpoint GET /api/productos/alertas.
     */
    public function enAlerta(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_actual' => 2,
            'stock_minimo' => 5,
        ]);
    }
}
