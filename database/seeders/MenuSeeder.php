<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Menus hebdomadaires reels (jours, periodes, semaines du cycle, plats) et parametres de rotation.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('week_menu_days')->upsert([
            ['id' => 1, 'code' => 'lundi', 'position' => 1],
            ['id' => 2, 'code' => 'mardi', 'position' => 2],
            ['id' => 3, 'code' => 'mercredi', 'position' => 3],
            ['id' => 4, 'code' => 'jeudi', 'position' => 4],
            ['id' => 5, 'code' => 'vendredi', 'position' => 5],
            ['id' => 6, 'code' => 'samedi', 'position' => 6],
            ['id' => 7, 'code' => 'dimanche', 'position' => 7],
        ], ['id']);

        DB::table('week_menu_periods')->upsert([
            ['id' => 1, 'code' => 'midi', 'position' => 1],
            ['id' => 2, 'code' => 'soir', 'position' => 2],
        ], ['id']);

        DB::table('menu_weeks')->upsert([
            ['id' => 1, 'name' => 'Semaine 1', 'position' => 1, 'in_cycle' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
            ['id' => 2, 'name' => 'Semaine 2', 'position' => 2, 'in_cycle' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
            ['id' => 3, 'name' => 'Semaine 3', 'position' => 3, 'in_cycle' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
            ['id' => 4, 'name' => 'Semaine 4', 'position' => 4, 'in_cycle' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
        ], ['id']);

        DB::table('week_menus')->upsert([
            ['id' => 1, 'week_id' => 1, 'day_id' => 1, 'period_id' => 1, 'dish_name' => 'Crudités, vinaigrette au citron.  Poulet yassa
Riz basmati', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-11-02 17:10:44'],
            ['id' => 2, 'week_id' => 1, 'day_id' => 1, 'period_id' => 2, 'dish_name' => 'Tagliatelles à la bolognaise
Courgettes et carottes sautées', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 3, 'week_id' => 1, 'day_id' => 2, 'period_id' => 1, 'dish_name' => 'Velouté de courge au gingembre. Riz jollof au poisson', 'description' => null, 'price' => 700, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 4, 'week_id' => 1, 'day_id' => 2, 'period_id' => 2, 'dish_name' => 'Échine de porc marinée et rôtie 
Plantain braisé
Potiron ou courge rôti au four', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 5, 'week_id' => 1, 'day_id' => 3, 'period_id' => 1, 'dish_name' => 'Salade avocat-tomate. Sauce arachide au poulet fumé
Foutou igname', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 6, 'week_id' => 1, 'day_id' => 3, 'period_id' => 2, 'dish_name' => 'Poisson braisé à l\'attiéké
Salade oignon-tomate-concombre', 'description' => null, 'price' => 950, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 7, 'week_id' => 1, 'day_id' => 4, 'period_id' => 1, 'dish_name' => 'Salade de crudités. Placali sauce gombo
bœuf, poisson fumé, crabe ·', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 8, 'week_id' => 1, 'day_id' => 4, 'period_id' => 2, 'dish_name' => 'Poulet rôti entier 
Pommes de terre, carottes et haricots verts rôtis au four', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 9, 'week_id' => 1, 'day_id' => 5, 'period_id' => 1, 'dish_name' => 'Salade composée. Haricots rouges, beignets, bouillie de mil
« BHB » · Cameroun', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 10, 'week_id' => 1, 'day_id' => 5, 'period_id' => 2, 'dish_name' => 'Soupe de poisson (mâchoiron)
Chikwangue ou riz', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 11, 'week_id' => 1, 'day_id' => 6, 'period_id' => 1, 'dish_name' => 'Carottes râpées, œufs durs. Tajine de poulet aux légumes
Couscous', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 12, 'week_id' => 1, 'day_id' => 6, 'period_id' => 2, 'dish_name' => 'Omelette espagnole aux pommes de terre
Salade verte', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 13, 'week_id' => 1, 'day_id' => 7, 'period_id' => 1, 'dish_name' => 'Salade verte. Kedjenou de poulet
Choukouya de chèvre
Poisson braisé, sauce feuille
Attiéké, alloco, riz, légumes vapeur', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-10-31 18:58:12'],
            ['id' => 14, 'week_id' => 1, 'day_id' => 7, 'period_id' => 2, 'dish_name' => 'Chef special dinner', 'description' => null, 'price' => 1500, 'available' => 1, 'created_at' => '2026-01-23 17:14:29', 'updated_at' => '2026-01-23 17:14:29'],
            ['id' => 15, 'week_id' => 2, 'day_id' => 1, 'period_id' => 1, 'dish_name' => 'Chou et carottes râpés	Riz cantonnais
Poisson au four', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 16, 'week_id' => 2, 'day_id' => 1, 'period_id' => 2, 'dish_name' => 'Émincé de porc aux poivrons et oignons
Riz parfumé
Courgettes sautées', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 17, 'week_id' => 2, 'day_id' => 2, 'period_id' => 1, 'dish_name' => 'Salade avocat-carotte	Ndolé au bœuf et aux crevettes
Plantain vapeur, chikwangue', 'description' => null, 'price' => 700, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 18, 'week_id' => 2, 'day_id' => 2, 'period_id' => 2, 'dish_name' => 'PLapin à la moutarde
Pommes de terre vapeur
Haricots verts et champignons sautés', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 19, 'week_id' => 2, 'day_id' => 3, 'period_id' => 1, 'dish_name' => 'Quiche aux légumes	Thiéboudienne
Riz au poisson et aux légumes', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 20, 'week_id' => 2, 'day_id' => 3, 'period_id' => 2, 'dish_name' => 'Poisson au four, attiéké
Brocolis et chou-fleur vapeur', 'description' => null, 'price' => 950, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 21, 'week_id' => 2, 'day_id' => 4, 'period_id' => 1, 'dish_name' => 'Salade de gésiers	Poulet DG
Plantain mûr, légumes
Cameroun', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 22, 'week_id' => 2, 'day_id' => 4, 'period_id' => 2, 'dish_name' => 'Canard rôti, sauce à l\'orange
Patates douces et courge rôties au four
Brocolis vapeur', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 23, 'week_id' => 2, 'day_id' => 5, 'period_id' => 1, 'dish_name' => 'Salade de tomates	Haricots blancs à la tomate, bouillie de mil', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 24, 'week_id' => 2, 'day_id' => 5, 'period_id' => 2, 'dish_name' => 'Soupe de poisson à la tomate
Igname bouillie
Haricots verts', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 25, 'week_id' => 2, 'day_id' => 6, 'period_id' => 1, 'dish_name' => 'Potage de légumes. Placali sauce gombo', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 26, 'week_id' => 2, 'day_id' => 6, 'period_id' => 2, 'dish_name' => 'Lasagnes à la viande
Courgettes, carottes vapeur', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 27, 'week_id' => 2, 'day_id' => 7, 'period_id' => 1, 'dish_name' => 'Salade composée	Kedjenou de poulet
Choukouya de chèvre
Poisson braisé
Sauce feuille, sauce tomate
Riz basmati, alloco, légumes vapeur', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:16:21'],
            ['id' => 28, 'week_id' => 2, 'day_id' => 7, 'period_id' => 2, 'dish_name' => 'Chef special dinner', 'description' => null, 'price' => 1500, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
            ['id' => 29, 'week_id' => 3, 'day_id' => 1, 'period_id' => 1, 'dish_name' => 'Salade de betteraves et carottes. Sauce claire de bœuf (pépé soupe)
Riz basmati', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 30, 'week_id' => 3, 'day_id' => 1, 'period_id' => 2, 'dish_name' => 'Soupe de poisson à l\'attiéké
Courge rôtie au four', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 31, 'week_id' => 3, 'day_id' => 2, 'period_id' => 1, 'dish_name' => 'Salade verte. Sauce pistache au poulet fumé
Foutou de banane plantain', 'description' => null, 'price' => 700, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 32, 'week_id' => 3, 'day_id' => 2, 'period_id' => 2, 'dish_name' => 'Gigot de mouton rôti à l\'ail
Pommes de terre et carottes au four
Haricots verts et champignons', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 33, 'week_id' => 3, 'day_id' => 3, 'period_id' => 1, 'dish_name' => 'Salade d\'avocat. Riz gras au poulet', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 34, 'week_id' => 3, 'day_id' => 3, 'period_id' => 2, 'dish_name' => 'Poisson braisé
Fonio aux légumes', 'description' => null, 'price' => 950, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 35, 'week_id' => 3, 'day_id' => 4, 'period_id' => 1, 'dish_name' => 'Salade de crudités. Pondu au poisson fumé
Chikwangue', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 36, 'week_id' => 3, 'day_id' => 4, 'period_id' => 2, 'dish_name' => 'Côtelettes de porc marinées et grillées
Pommes de terre sautées
Brocolis et chou-fleur vapeur', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 37, 'week_id' => 3, 'day_id' => 5, 'period_id' => 1, 'dish_name' => 'Salade tomate-concombre. Voandzou (pois bambara) à la sauce tomate
Poisson frit, riz', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 38, 'week_id' => 3, 'day_id' => 5, 'period_id' => 2, 'dish_name' => 'Poisson pané maison
Igname au four
Crudités', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 39, 'week_id' => 3, 'day_id' => 6, 'period_id' => 1, 'dish_name' => 'Salade de pâtes. Sauce graine, tête-patte-queue de bœuf
Foutou banane', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 40, 'week_id' => 3, 'day_id' => 6, 'period_id' => 2, 'dish_name' => 'Choukouya de poulet
Riz cantonnais
Haricots verts à l\'ail', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 41, 'week_id' => 3, 'day_id' => 7, 'period_id' => 1, 'dish_name' => 'Salade verte. Choukouya de mouton
Lapin braisé
Poisson braisé, sauce feuille
Riz, alloco, légumes vapeur', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:13'],
            ['id' => 42, 'week_id' => 3, 'day_id' => 7, 'period_id' => 2, 'dish_name' => 'Chef special dinner', 'description' => null, 'price' => 1500, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
            ['id' => 43, 'week_id' => 4, 'day_id' => 1, 'period_id' => 1, 'dish_name' => 'Potage de légumes	Gouagouassou
Placali', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 44, 'week_id' => 4, 'day_id' => 1, 'period_id' => 2, 'dish_name' => 'Soupe de mâchoiron
Igname bouillie
Brocolis et carottes vapeur', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 45, 'week_id' => 4, 'day_id' => 2, 'period_id' => 1, 'dish_name' => 'Salade avocat-carotte	Gboma dessi (sauce épinard) au poisson fumé
Pâte de maïs (akoumé)', 'description' => null, 'price' => 700, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 46, 'week_id' => 4, 'day_id' => 2, 'period_id' => 2, 'dish_name' => 'Sole braisée
Attiéké
Courgettes grillées', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 47, 'week_id' => 4, 'day_id' => 3, 'period_id' => 1, 'dish_name' => 'Quiche lorraine	Wachi (riz-haricot) sauce tomate
Œuf dur, wagashi, poisson', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 48, 'week_id' => 4, 'day_id' => 3, 'period_id' => 2, 'dish_name' => 'Poulet au four
Gratin dauphinois
Salade verte', 'description' => null, 'price' => 950, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 49, 'week_id' => 4, 'day_id' => 4, 'period_id' => 1, 'dish_name' => 'Pizza maison 	Soupe egusi
Igname pilée', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 50, 'week_id' => 4, 'day_id' => 4, 'period_id' => 2, 'dish_name' => 'Canard aux champignons
Riz pilaf
Potiron ou courge rôti au four', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 51, 'week_id' => 4, 'day_id' => 5, 'period_id' => 1, 'dish_name' => 'Salade d\'avocat	Red red : niébé à l\'huile rouge
Plantain frit (kelewele), poisson', 'description' => null, 'price' => 800, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 52, 'week_id' => 4, 'day_id' => 5, 'period_id' => 2, 'dish_name' => 'Liboké de poisson
Chikwangue', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 53, 'week_id' => 4, 'day_id' => 6, 'period_id' => 1, 'dish_name' => 'Salade composée	Poulet à la moambe
Sauce noix de palme
Plantain vapeur', 'description' => null, 'price' => 1000, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 54, 'week_id' => 4, 'day_id' => 6, 'period_id' => 2, 'dish_name' => 'Pâtes sauce tomate au thon
Salade verte', 'description' => null, 'price' => 900, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 55, 'week_id' => 4, 'day_id' => 7, 'period_id' => 1, 'dish_name' => 'Salade verte	Placali sauce gombo
Poulet braisé, poisson braisé, calamars panés
Légumes vapeur, frites d\'igname', 'description' => null, 'price' => 1200, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:09:28'],
            ['id' => 56, 'week_id' => 4, 'day_id' => 7, 'period_id' => 2, 'dish_name' => 'Chef special dinner', 'description' => null, 'price' => 1500, 'available' => 1, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-10-26 09:18:01'],
        ], ['id']);

        DB::table('menu_settings')->upsert([
            ['id' => 1, 'active_week_id' => 3, 'auto_rotation' => 0, 'anchor_week_id' => null, 'anchor_week_start' => null, 'created_at' => '2026-10-26 09:18:01', 'updated_at' => '2026-11-02 17:03:29'],
        ], ['id']);

    }
}
