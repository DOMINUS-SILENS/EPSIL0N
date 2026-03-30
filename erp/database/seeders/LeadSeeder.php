<?php

namespace Database\Seeders;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $entrepriseIds = Entreprise::pluck('entreprise_id')->toArray();
        $userIds = User::pluck('id')->toArray();
        $firstEntrepriseId = $entrepriseIds[0] ?? 1;
        $salesUserId = $userIds[2] ?? 1; // Sales rep

        $leads = [
            [
                'entreprise_id' => $firstEntrepriseId,
                'name' => 'Maroc Telecom',
                'email' => 'procurement@iam.ma',
                'phone' => '+212 520 123 456',
                'company' => 'IAM',
                'status' => 'qualified',
                'source' => 'website',
                'notes' => 'Grand client potentiel - besoin en équipement IT',
                'assigned_to' => $salesUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'name' => 'OCP Group',
                'email' => 'achats@ocp.ma',
                'phone' => '+212 522 987 654',
                'company' => 'OCP',
                'status' => 'new',
                'source' => 'referral',
                'notes' => 'Contact initié via partenaire',
                'assigned_to' => $salesUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'name' => 'Royal Air Maroc',
                'email' => 'fournisseurs@ram.ma',
                'phone' => '+212 522 456 789',
                'company' => 'RAM',
                'status' => 'contacted',
                'source' => 'trade_show',
                'notes' => 'Rencontré au salon aéronautique',
                'assigned_to' => $salesUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'name' => 'Attijariwafa Bank',
                'email' => 'appro@attijariwafabank.com',
                'phone' => '+212 520 789 123',
                'company' => 'Attijariwafa',
                'status' => 'proposal',
                'source' => 'cold_call',
                'notes' => 'Proposition envoyée, en attente de réponse',
                'assigned_to' => $salesUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $entrepriseIds[1] ?? $firstEntrepriseId,
                'name' => 'Cosumar',
                'email' => 'achats@cosumar.ma',
                'phone' => '+212 522 345 678',
                'company' => 'Cosumar',
                'status' => 'negotiation',
                'source' => 'website',
                'notes' => 'En négociation sur les prix',
                'assigned_to' => $salesUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $firstEntrepriseId,
                'name' => 'Maghreb Steel',
                'email' => 'fournisseurs@maghrebsteel.ma',
                'phone' => '+212 522 876 543',
                'company' => 'Maghreb Steel',
                'status' => 'lost',
                'source' => 'email',
                'notes' => 'Projet annulé par le client',
                'assigned_to' => $salesUserId,
                'created_at' => now()->subDays(30),
                'updated_at' => now(),
            ],
            [
                'entreprise_id' => $entrepriseIds[2] ?? $firstEntrepriseId,
                'name' => 'Lesieur Cristal',
                'email' => 'achats@lesieur.ma',
                'phone' => '+212 520 567 890',
                'company' => 'Lesieur',
                'status' => 'won',
                'source' => 'referral',
                'notes' => 'Contrat signé - livraison mensuelle',
                'assigned_to' => $salesUserId,
                'created_at' => now()->subDays(60),
                'updated_at' => now(),
            ],
        ];

        foreach ($leads as $lead) {
            DB::table('crm_leads')->insert($lead);
        }
    }
}
