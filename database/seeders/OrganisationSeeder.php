<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Organisation;
use App\Models\User;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Hash;

class OrganisationSeeder extends Seeder {
    public function run(): void {
        $orgs = [
            ["name" => "Pro Business Advisors Ltd", "slug" => "pba"],
            ["name" => "PC Express Limited",        "slug" => "pce"],
            ["name" => "PC Express Business Limited","slug" => "pceb"],
        ];

        foreach ($orgs as $orgData) {
            $org = Organisation::firstOrCreate(["slug" => $orgData["slug"]], array_merge($orgData, [
                "leave_year_month_start" => 4,
                "default_holiday_days" => 28,
                "max_carry_forward_days" => 5,
            ]));

            $leaveTypes = [
                ["name" => "Annual Leave",   "colour" => "#3b82f6", "carries_forward" => true,  "paid" => true],
                ["name" => "Sick",           "colour" => "#ef4444", "carries_forward" => false, "paid" => false],
                ["name" => "Compassionate",  "colour" => "#8b5cf6", "carries_forward" => false, "paid" => true],
                ["name" => "Parental",       "colour" => "#ec4899", "carries_forward" => false, "paid" => true],
                ["name" => "Work from Home", "colour" => "#10b981", "carries_forward" => false, "paid" => true, "requires_approval" => false],
                ["name" => "TOIL",           "colour" => "#f59e0b", "carries_forward" => true,  "paid" => true],
            ];
            foreach ($leaveTypes as $i => $lt) {
                LeaveType::firstOrCreate(["org_id" => $org->id, "name" => $lt["name"]], array_merge($lt, ["org_id" => $org->id, "sort_order" => $i]));
            }
            echo "Seeded: {$org->name}\n";
        }

        // Global admin
        $admin = User::firstOrCreate(["email" => "admin@hr.local"], [
            "name" => "Admin",
            "first_name" => "System",
            "last_name" => "Admin",
            "email" => "admin@hr.local",
            "password" => Hash::make("change-me-2026!"),
            "role" => "global_admin",
            "active" => true,
        ]);
        echo "Admin user: admin@hr.local / change-me-2026!\n";
    }
}