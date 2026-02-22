<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\LocationImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class LocationImageSeeder extends Seeder
{
    /**
     * صور حقيقية للنيل والمراكب - من Unsplash (ترخيص مجاني)
     * مراكب نيلية، نهر النيل، القاهرة، مصر
     */
    private array $imageUrls = [
        'https://images.unsplash.com/photo-1719659018185-8a239c35fb4a?w=800', // مركب شراعي والنيل والقاهرة - حاتم رمضان
        'https://images.unsplash.com/photo-1716639154156-db53b75a22ad?w=800', // مركب شراعي ومدينة في الخلفية
        'https://images.unsplash.com/photo-1623674567450-b600b67864a6?w=800', // ركاب على مركب في النهر
        'https://images.unsplash.com/photo-1652258674821-901b30240160?w=800', // مجموعة مراكب ومباني - النيل
        'https://images.unsplash.com/photo-1680356217112-dad9300ce49d?w=800', // ماء ونخيل - منظر مصري
        'https://images.unsplash.com/photo-1616187779087-8a863b5f277b?w=800', // مركب أحمر على النيل - أحمد بابكر
        'https://images.unsplash.com/photo-1663596680812-0df611df4fe0?w=800', // مركب على الماء - أحمد أجمعي
        'https://images.unsplash.com/photo-1684100096410-fd39cdff91a3?w=800', // مركب شراعي عند الغروب
        'https://images.unsplash.com/photo-1644517270263-4112379d97ca?w=800', // مركب شراعي وجبل
    ];

    public function run(): void
    {
        $locations = Location::all();
        $urlIndex = 0;

        foreach ($locations as $location) {
            $urls = array_slice($this->imageUrls, $urlIndex % count($this->imageUrls), 3);
            if (empty($urls)) {
                $urls = array_slice($this->imageUrls, 0, 3);
            }

            $sortOrder = 0;
            foreach ($urls as $url) {
                try {
                    $response = Http::timeout(10)->get($url);
                    if ($response->successful()) {
                        $imageData = $response->body();
                        $extension = 'jpg';
                        $filename = 'img_' . uniqid() . '.' . $extension;
                        $path = 'locations/' . $location->id . '/' . $filename;

                        Storage::disk('public')->put($path, $imageData);

                        LocationImage::create([
                            'location_id' => $location->id,
                            'path' => $path,
                            'alt_text' => $location->name_ar,
                            'sort_order' => $sortOrder,
                            'is_primary' => $sortOrder === 0,
                        ]);
                        $sortOrder++;
                    }
                } catch (\Exception $e) {
                    $this->command->warn("فشل تحميل صورة للموقع {$location->id}: " . $e->getMessage());
                }
            }
            $urlIndex += 3;
        }
    }
}
