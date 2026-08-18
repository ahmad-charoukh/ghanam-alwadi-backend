<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ContentPageSeeder::class);

        $adminName = trim(
            (string) env('ADMIN_NAME', 'Ahmad Charoukh')
        );

        $adminEmail = trim(
            (string) env('ADMIN_EMAIL')
        );

        $adminPassword = (string) env('ADMIN_PASSWORD');

        if ($adminEmail === '' || $adminPassword === '') {
            throw new \RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD are required.'
            );
        }

        $admin = User::query()->firstOrNew([
            'email' => $adminEmail,
        ]);

        $admin->name = $adminName;
        $admin->is_admin = true;
        $admin->email_verified_at ??= now();

        if (! $admin->exists) {
            $admin->password = $adminPassword;
        }

        $admin->save();

        $category = Category::query()->updateOrCreate(
            [
                'slug' => 'sheep',
            ],
            [
                'name' => 'الأغنام',
                'description' => 'تشكيلة الأغنام المتوفرة للطلب',
                'image' => null,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        Product::query()->updateOrCreate(
            [
                'name' => 'خروف نعيمي',
            ],
            [
                'category_id' => $category->id,
                'category' => $category->name,
                'price' => 1500,
                'stock' => 10,
                'image' => null,
                'description' => 'خروف نعيمي متوفر للطلب داخل الرياض.',
                'is_active' => true,
                'is_featured' => true,
            ]
        );
    }
}