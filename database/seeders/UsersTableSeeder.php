<?php

namespace Database\Seeders;

use App\Enums\MaritalStatus;
use App\Models\ClientDetails;
use App\Models\EmployeeDetails;
use App\Models\Role;
use App\Models\UniversalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run($companyId)
    {

        $count = config('app.seed_record_count');

        $adminRole = Role::where('name', 'admin')->where('company_id', $companyId)->first();
        $employeeRole = Role::where('name', 'employee')->where('company_id', $companyId)->first();
        $clientRole = Role::where('name', 'client')->where('company_id', $companyId)->first();


        $user = new User();
        $user->name = 'Admin User ' . $companyId;
        $user->company_id = $companyId;

        if ($companyId === 1) {
            $user->email = 'admin@example.com';
            $user->password = Hash::make('123456');
            $user->gender = 'male';
            $user->save();

            $this->addEmployeeDetails($user, $employeeRole, $companyId);
            $user->roles()->attach($adminRole->id); // id only

            $user = new User();
            $user->name = 'Employee User ' . $companyId;
            $user->company_id = $companyId;
            $user->email = 'employee@example.com';
            $user->password = Hash::make('123456');
            $user->gender = 'male';
            $user->save();

            $this->addEmployeeDetails($user, $employeeRole, $companyId);

            // Client details
            $user = new User();
            $user->name = 'Client User ' . $companyId;
            $user->company_id = $companyId;
            $user->email = 'client@example.com';
        }
        else {
            $user->email = 'admin' . $companyId . '@example.com';
            $user->password = Hash::make('123456');
            $user->gender = 'male';
            $user->save();

            $this->addEmployeeDetails($user, $employeeRole, $companyId);
            $user->roles()->attach($adminRole->id); // id only

            $user = new User();
            $user->name = 'Employee User ' . $companyId;
            $user->company_id = $companyId;
            $user->email = 'employee' . $companyId . '@example.com';
            $user->password = Hash::make('123456');
            $user->gender = 'male';
            $user->save();

            $this->addEmployeeDetails($user, $employeeRole, $companyId);

            // Client details
            $user = new User();
            $user->name = 'Client User ' . $companyId;
            $user->company_id = $companyId;
            $user->email = 'client' . $companyId . '@example.com';

        }

        $user->password = Hash::make('123456');
        $user->save();
        $this->addClientDetails($user, $clientRole, $companyId);


        // Keep seeding deterministic and avoid optional Faker dependency.
        if ((int)$count > 0) {
            for ($i = 1; $i <= (int)$count; $i++) {
                $client = new User();
                $client->name = 'Client Seed ' . $companyId . '-' . $i;
                $client->company_id = $companyId;
                $client->email = 'client' . $companyId . '_' . $i . '@example.com';
                $client->password = Hash::make('123456');
                $client->save();
                $this->addClientDetails($client, $clientRole, $companyId);
            }

            for ($i = 1; $i <= (int)$count; $i++) {
                $employee = new User();
                $employee->name = 'Employee Seed ' . $companyId . '-' . $i;
                $employee->company_id = $companyId;
                $employee->email = 'employee' . $companyId . '_' . $i . '@example.com';
                $employee->password = Hash::make('123456');
                $employee->gender = 'male';
                $employee->save();
                $this->addEmployeeDetails($employee, $employeeRole, $companyId);
            }
        }
    }

    private function addEmployeeDetails($user, $employeeRole, $companyId)
    {
        $employee = new EmployeeDetails();
        $employee->user_id = $user->id;
        $employee->company_id = $companyId;
        /* @phpstan-ignore-line */
        $employee->employee_id = 'EMP-' . (EmployeeDetails::where('company_id', $companyId)->count() + 1);
        /* @phpstan-ignore-line */
        $employee->address = 'Default employee address';
        $employee->about_me = 'I am super human';
        $employee->hourly_rate = rand(15, 100);
        $employee->department_id = rand(1, 6);
        $employee->designation_id = rand(1, 5);
        $employee->joining_date = now()->subMonths(9)->toDateTimeString();
        $employee->calendar_view = 'task,events,holiday,tickets,leaves';
        $employee->marital_status = MaritalStatus::Single;
        $employee->save();

        $search = new UniversalSearch();
        $search->searchable_id = $user->id;
        $search->company_id = $companyId;
        $search->title = $user->name;
        $search->route_name = 'employees.show';
        $search->save();

        // Assign Role
        $user->roles()->attach($employeeRole->id);
        /* @phpstan-ignore-line */
    }

    private function addClientDetails($user, $clientRole, $companyId)
    {
        $search = new UniversalSearch();
        $search->searchable_id = $user->id;
        $search->company_id = $companyId;
        /* @phpstan-ignore-line */
        $search->title = $user->name;
        /* @phpstan-ignore-line */
        $search->route_name = 'clients.show';
        $search->save();

        $client = new ClientDetails();
        $client->user_id = $user->id;
        $client->company_id = $companyId;
        /* @phpstan-ignore-line */
        $client->company_name = 'Client Company ' . $companyId;
        $client->address = 'Default client address';
        $client->website = 'https://worksuite.biz';
        $client->save();

        // Assign Role
        $user->roles()->attach($clientRole->id);
        /* @phpstan-ignore-line */
    }

}
