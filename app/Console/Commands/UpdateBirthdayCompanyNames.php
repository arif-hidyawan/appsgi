<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BirthdayReminder;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\VendorContact;
use App\Models\CustomerContact;

class UpdateBirthdayCompanyNames extends Command
{
    protected $signature = 'birthday:update-company-names';
    protected $description = 'Updates missing company_name for PIC records in birthday_reminders table.';

    public function handle()
    {
        $remindersToUpdate = BirthdayReminder::whereNull('company_name')
            ->whereIn('type', ['PIC Vendor', 'PIC Customer'])
            ->get();
        
        $updatedCount = 0;

        $this->info("Found " . $remindersToUpdate->count() . " PIC reminders with missing company name.");

        foreach ($remindersToUpdate as $reminder) {
            $companyName = null;

            if ($reminder->type === 'PIC Vendor') {
                // Cari PIC Vendor berdasarkan namanya
                $contact = VendorContact::where('pic_name', $reminder->name)->first();
                // Asumsi VendorContact memiliki relasi belongsTo('Vendor')
                $companyName = optional($contact->vendor)->name;
            } elseif ($reminder->type === 'PIC Customer') {
                // Cari PIC Customer berdasarkan namanya
                $contact = CustomerContact::where('pic_name', $reminder->name)->first();
                // Asumsi CustomerContact memiliki relasi belongsTo('Customer')
                $companyName = optional($contact->customer)->name;
            }

            if ($companyName) {
                $reminder->company_name = $companyName;
                $reminder->save();
                $this->line("Updated reminder ID {$reminder->id} ({$reminder->name}) with company: {$companyName}");
                $updatedCount++;
            } else {
                $this->warn("Could not find company for reminder ID {$reminder->id} ({$reminder->name}). Skipping.");
            }
        }

        $this->info("Successfully updated {$updatedCount} reminders.");
        return 0;
    }
}