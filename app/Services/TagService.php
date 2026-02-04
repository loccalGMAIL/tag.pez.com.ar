<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AppSetting;

class TagService
{
    /**
     * Obtener el ShopCode desde la configuración
     */
    private function getShopCode(): string
    {
        return AppSetting::get('default_shop_code', env('ERETAIL_DEFAULT_SHOP_CODE', '0010'));
    }

    /**
     * Obtener todas las etiquetas con información de productos vinculados
     */
    public function getAllTags(): array
    {
        try {
            $shopCode = $this->getShopCode();

            // Obtener etiquetas con sus productos vinculados
            $tags = DB::connection('eretail')
                ->table('Tag')
                ->leftJoin('GoodsBind', 'Tag.Id', '=', 'GoodsBind.DeviceId')
                ->leftJoin('Goods', 'GoodsBind.GoodsId', '=', 'Goods.Id')
                ->where('Tag.ShopCode', $shopCode)
                ->where('Tag.IsDeleted', 0)
                ->select([
                    'Tag.Id as tag_id',
                    'Tag.TagType as tag_type',
                    'Tag.TagStatus as tag_status',
                    'Tag.PowerVal as power_val',
                    'Tag.Temperature as temperature',
                    'Tag.Rssi as rssi',
                    'Tag.LastSendTime as last_send_time',
                    'Tag.LastRecvTime as last_recv_time',
                    'Tag.TotalSend as total_send',
                    'Tag.ErrorCount as error_count',
                    'Tag.DefaultAP as default_ap',
                    'Goods.GoodsCode as goods_code',
                    'Goods.GoodsName as goods_name',
                    'Goods.Template as template',
                ])
                ->orderBy('Tag.LastRecvTime', 'desc')
                ->get();

            return $tags->map(function ($tag) {
                return [
                    'tag_id' => $tag->tag_id,
                    'tag_type' => $tag->tag_type,
                    'tag_status' => $tag->tag_status,
                    'status_text' => $this->getStatusText($tag->tag_status),
                    'status_color' => $this->getStatusColor($tag->tag_status),
                    'power_val' => $tag->power_val,
                    'power_percent' => $this->getPowerPercent($tag->power_val),
                    'temperature' => $tag->temperature,
                    'rssi' => $tag->rssi,
                    'signal_quality' => $this->getSignalQuality($tag->rssi),
                    'last_send_time' => $tag->last_send_time,
                    'last_recv_time' => $tag->last_recv_time,
                    'total_send' => $tag->total_send,
                    'error_count' => $tag->error_count,
                    'default_ap' => $tag->default_ap,
                    'goods_code' => $tag->goods_code,
                    'goods_name' => $tag->goods_name,
                    'template' => $tag->template,
                    'has_product' => !empty($tag->goods_code),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error obteniendo etiquetas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener una etiqueta por ID
     */
    public function getTagById(string $tagId): ?array
    {
        try {
            $shopCode = $this->getShopCode();

            $tag = DB::connection('eretail')
                ->table('Tag')
                ->leftJoin('GoodsBind', 'Tag.Id', '=', 'GoodsBind.DeviceId')
                ->leftJoin('Goods', 'GoodsBind.GoodsId', '=', 'Goods.Id')
                ->where('Tag.Id', $tagId)
                ->where('Tag.ShopCode', $shopCode)
                ->select([
                    'Tag.Id as tag_id',
                    'Tag.TagType as tag_type',
                    'Tag.TagStatus as tag_status',
                    'Tag.PowerVal as power_val',
                    'Tag.Temperature as temperature',
                    'Tag.Rssi as rssi',
                    'Tag.LastSendTime as last_send_time',
                    'Tag.LastRecvTime as last_recv_time',
                    'Tag.TotalSend as total_send',
                    'Tag.ErrorCount as error_count',
                    'Tag.DefaultAP as default_ap',
                    'Goods.GoodsCode as goods_code',
                    'Goods.GoodsName as goods_name',
                    'Goods.Template as template',
                ])
                ->first();

            if (!$tag) {
                return null;
            }

            return [
                'tag_id' => $tag->tag_id,
                'tag_type' => $tag->tag_type,
                'tag_status' => $tag->tag_status,
                'status_text' => $this->getStatusText($tag->tag_status),
                'power_val' => $tag->power_val,
                'power_percent' => $this->getPowerPercent($tag->power_val),
                'temperature' => $tag->temperature,
                'rssi' => $tag->rssi,
                'signal_quality' => $this->getSignalQuality($tag->rssi),
                'last_send_time' => $tag->last_send_time,
                'last_recv_time' => $tag->last_recv_time,
                'total_send' => $tag->total_send,
                'error_count' => $tag->error_count,
                'default_ap' => $tag->default_ap,
                'goods_code' => $tag->goods_code,
                'goods_name' => $tag->goods_name,
                'template' => $tag->template,
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo etiqueta {$tagId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener texto de estado
     */
    private function getStatusText(int $status): string
    {
        return match ($status) {
            0 => 'Sin usar',
            1 => 'Normal',
            2 => 'Batería baja',
            3 => 'Comunicando',
            5 => 'Sospecha perdida',
            6 => 'Fallo final',
            7 => 'Heartbeat',
            default => 'Desconocido',
        };
    }

    /**
     * Obtener color de estado
     */
    private function getStatusColor(int $status): string
    {
        return match ($status) {
            0 => 'gray',
            1 => 'green',
            2 => 'yellow',
            3 => 'blue',
            5 => 'orange',
            6 => 'red',
            7 => 'green',
            default => 'gray',
        };
    }

    /**
     * Convertir valor de batería a porcentaje
     */
    private function getPowerPercent(?int $powerVal): int
    {
        if ($powerVal === null) {
            return 0;
        }

        // Según documentación eRetail
        return match (true) {
            $powerVal >= 30 => 100,
            $powerVal >= 28 => 90,
            $powerVal >= 27 => 80,
            $powerVal >= 26 => 60,
            $powerVal >= 25 => 40,
            $powerVal >= 24 => 20,
            $powerVal >= 23 => 10,
            default => 0,
        };
    }

    /**
     * Obtener calidad de señal
     */
    private function getSignalQuality(?int $rssi): string
    {
        if ($rssi === null) {
            return 'Sin datos';
        }

        return match (true) {
            $rssi >= -50 => 'Excelente',
            $rssi >= -60 => 'Muy buena',
            $rssi >= -70 => 'Buena',
            $rssi >= -80 => 'Regular',
            $rssi >= -90 => 'Débil',
            default => 'Muy débil',
        };
    }

    /**
     * Obtener Tag IDs por goodsCodes (ProductVariant IDs)
     *
     * Relación: ProductVariant.id → Goods.GoodsCode → Goods.Id → GoodsBind.GoodsId → GoodsBind.DeviceId → Tag.Id
     */
    public function getTagIdsByGoodsCodes(array $goodsCodes, ?string $shopCode = null): array
    {
        if (empty($goodsCodes)) {
            return [];
        }

        try {
            $shopCode = $shopCode ?? $this->getShopCode();
            $goodsCodesStr = array_map('strval', $goodsCodes);

            Log::info('Buscando Tag IDs en eRetail', [
                'goodsCodes' => $goodsCodesStr,
                'shopCode' => $shopCode
            ]);

            // Primero verificar si los productos existen en la tabla Goods
            $existingGoods = DB::connection('eretail')
                ->table('Goods')
                ->whereIn('GoodsCode', $goodsCodesStr)
                ->select('Id', 'GoodsCode', 'GoodsName')
                ->get();

            Log::info('Productos encontrados en Goods', [
                'buscados' => count($goodsCodesStr),
                'encontrados' => $existingGoods->count(),
                'goodsIds' => $existingGoods->pluck('Id')->toArray()
            ]);

            if ($existingGoods->isEmpty()) {
                Log::warning('No se encontraron productos en tabla Goods para los GoodsCodes buscados');
                return [];
            }

            // Verificar vinculaciones en GoodsBind
            $goodsIds = $existingGoods->pluck('Id')->toArray();
            $bindings = DB::connection('eretail')
                ->table('GoodsBind')
                ->whereIn('GoodsId', $goodsIds)
                ->select('GoodsId', 'DeviceId')
                ->get();

            Log::info('Vinculaciones encontradas en GoodsBind', [
                'goodsIds_buscados' => count($goodsIds),
                'bindings_encontrados' => $bindings->count(),
                'deviceIds' => $bindings->pluck('DeviceId')->toArray()
            ]);

            if ($bindings->isEmpty()) {
                Log::warning('No hay vinculaciones en GoodsBind para los productos');
                return [];
            }

            // Obtener Tag IDs finales
            $deviceIds = $bindings->pluck('DeviceId')->toArray();
            $tagIds = DB::connection('eretail')
                ->table('Tag')
                ->where('ShopCode', $shopCode)
                ->where('IsDeleted', 0)
                ->whereIn('Id', $deviceIds)
                ->distinct()
                ->pluck('Id')
                ->toArray();

            Log::info('Tag IDs encontrados para goodsCodes', [
                'goodsCodes_count' => count($goodsCodes),
                'tagIds_found' => count($tagIds),
                'tagIds' => $tagIds
            ]);

            return $tagIds;

        } catch (\Exception $e) {
            Log::error('Error obteniendo Tag IDs: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Obtener estadísticas generales de etiquetas
     */
    public function getTagStats(): array
    {
        try {
            $shopCode = $this->getShopCode();

            $total = DB::connection('eretail')
                ->table('Tag')
                ->where('ShopCode', $shopCode)
                ->where('IsDeleted', 0)
                ->count();

            $vinculadas = DB::connection('eretail')
                ->table('Tag')
                ->join('GoodsBind', 'Tag.Id', '=', 'GoodsBind.DeviceId')
                ->where('Tag.ShopCode', $shopCode)
                ->where('Tag.IsDeleted', 0)
                ->count();

            $bateriaBaja = DB::connection('eretail')
                ->table('Tag')
                ->where('ShopCode', $shopCode)
                ->where('IsDeleted', 0)
                ->where('PowerVal', '<', 25)
                ->count();

            $offline = DB::connection('eretail')
                ->table('Tag')
                ->where('ShopCode', $shopCode)
                ->where('IsDeleted', 0)
                ->whereIn('TagStatus', [5, 6])
                ->count();

            return [
                'total' => $total,
                'vinculadas' => $vinculadas,
                'sin_vincular' => $total - $vinculadas,
                'bateria_baja' => $bateriaBaja,
                'offline' => $offline,
            ];

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas de etiquetas: ' . $e->getMessage());
            return [
                'total' => 0,
                'vinculadas' => 0,
                'sin_vincular' => 0,
                'bateria_baja' => 0,
                'offline' => 0,
            ];
        }
    }
}
