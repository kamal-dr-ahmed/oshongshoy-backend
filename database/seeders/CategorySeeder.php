<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name_bn' => 'ইতিহাস',
                'name_en' => 'History',
                'slug' => 'history',
                'description_bn' => 'ঐতিহাসিক ঘটনা ও বিশ্লেষণ',
                'description_en' => 'Historical events and analysis',
                'icon' => 'history',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name_bn' => 'রাজনীতি',
                'name_en' => 'Politics', 
                'slug' => 'politics',
                'description_bn' => 'রাজনৈতিক বিশ্লেষণ ও মতামত',
                'description_en' => 'Political analysis and opinions',
                'icon' => 'government',
                'color' => '#EF4444',
                'sort_order' => 2,
            ],
            [
                'name_bn' => 'ধর্ম ও দর্শন',
                'name_en' => 'Religion & Philosophy',
                'slug' => 'religion-philosophy', 
                'description_bn' => 'ধর্মীয় ও দার্শনিক আলোচনা',
                'description_en' => 'Religious and philosophical discussions',
                'icon' => 'book',
                'color' => '#10B981',
                'sort_order' => 3,
            ],
            [
                'name_bn' => 'অর্থনীতি',
                'name_en' => 'Economy',
                'slug' => 'economy',
                'description_bn' => 'অর্থনৈতিক বিশ্লেষণ ও তথ্য',
                'description_en' => 'Economic analysis and data',
                'icon' => 'chart',
                'color' => '#F59E0B',
                'sort_order' => 4,
            ],
            [
                'name_bn' => 'শিল্প ও সংস্কৃতি',
                'name_en' => 'Art & Culture',
                'slug' => 'art-culture',
                'description_bn' => 'সাংস্কৃতিক ঐতিহ্য, শিল্পকলা ও সাহিত্য',
                'description_en' => 'Cultural heritage, arts and literature',
                'icon' => 'palette',
                'color' => '#8B5CF6',
                'sort_order' => 5,
            ],
            [
                'name_bn' => 'বিজ্ঞান ও প্রযুক্তি',
                'name_en' => 'Science & Technology',
                'slug' => 'science-technology',
                'description_bn' => 'বৈজ্ঞানিক আবিষ্কার ও প্রযুক্তি',
                'description_en' => 'Scientific discoveries and technology',
                'icon' => 'science',
                'color' => '#06B6D4',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
