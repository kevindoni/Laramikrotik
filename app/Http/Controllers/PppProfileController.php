<?php

namespace App\Http\Controllers;

use App\Models\PppProfile;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PppProfileController extends Controller
{
    protected $mikrotikService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\MikrotikService  $mikrotikService
     * @return void
     */
    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
    }

    /**
     * Display a listing of the PPP profiles.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pppProfiles = PppProfile::orderBy('name')->paginate(15);
        return view('ppp-profiles.index', compact('pppProfiles'));
    }

    /**
     * Show the form for creating a new PPP profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('ppp-profiles.create');
    }

    /**
     * Store a newly created PPP profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:ppp_profiles',
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'download_speed' => 'nullable|string|max:50',
            'upload_speed' => 'nullable|string|max:50',
            'parent_queue' => 'nullable|string|max:255',
            'only_one' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'auto_sync' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-profiles.create')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        
        // Build rate_limit from download/upload speeds
        if (!empty($data['download_speed']) && !empty($data['upload_speed'])) {
            $data['rate_limit'] = $data['download_speed'] . '/' . $data['upload_speed'];
        } elseif (!empty($data['download_speed'])) {
            $data['rate_limit'] = $data['download_speed'] . '/' . $data['download_speed'];
        }
        
        // Remove individual speed fields
        unset($data['download_speed'], $data['upload_speed']);
        
        // Handle unchecked checkboxes
        $data['only_one'] = $data['only_one'] ?? false;
        $data['is_active'] = $data['is_active'] ?? false;
        $data['auto_sync'] = $data['auto_sync'] ?? false;

        $profile = PppProfile::create($data);

        return redirect()->route('ppp-profiles.show', $profile->id)
            ->with('success', 'PPP profile created successfully.');
    }

    /**
     * Display the specified PPP profile.
     *
     * @param  \App\Models\PppProfile  $pppProfile
     * @return \Illuminate\Http\Response
     */
    public function show(PppProfile $pppProfile)
    {
        $pppSecrets = $pppProfile->pppSecrets()->with('customer')->get();
        return view('ppp-profiles.show', [
            'pppProfile' => $pppProfile,
            'pppSecrets' => $pppSecrets
        ]);
    }

    /**
     * Show the form for editing the specified PPP profile.
     *
     * @param  \App\Models\PppProfile  $pppProfile
     * @return \Illuminate\Http\Response
     */
    public function edit(PppProfile $pppProfile)
    {
        return view('ppp-profiles.edit', ['pppProfile' => $pppProfile]);
    }

    /**
     * Update the specified PPP profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PppProfile  $pppProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PppProfile $pppProfile)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:ppp_profiles,name,' . $pppProfile->id,
            'local_address' => 'nullable|string|max:255',
            'remote_address' => 'nullable|string|max:255',
            'rate_limit' => 'nullable|string|max:255',
            'parent_queue' => 'nullable|string|max:255',
            'only_one' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sync_with_mikrotik' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-profiles.edit', $pppProfile->id)
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $syncWithMikrotik = $data['sync_with_mikrotik'] ?? false;
        unset($data['sync_with_mikrotik']);

        // Store the old name for MikroTik update
        $oldName = $pppProfile->name;
        
        $pppProfile->update($data);

        // Sync with MikroTik if requested
        if ($syncWithMikrotik) {
            try {
                $this->mikrotikService->connect();
                
                // If name changed, we need to delete and recreate
                if ($oldName !== $pppProfile->name) {
                    $this->mikrotikService->deletePppProfile($oldName);
                    $this->mikrotikService->createPppProfile($pppProfile);
                } else {
                    $this->mikrotikService->updatePppProfile($pppProfile);
                }
                
                $message = 'PPP profile updated successfully and synced with MikroTik.';
            } catch (Exception $e) {
                return redirect()->route('ppp-profiles.show', $pppProfile->id)
                    ->with('error', 'PPP profile updated but failed to sync with MikroTik: ' . $e->getMessage());
            }
        } else {
            $message = 'PPP profile updated successfully.';
        }

        return redirect()->route('ppp-profiles.show', $pppProfile->id)
            ->with('success', $message);
    }

    /**
     * Remove the specified PPP profile from storage.
     *
     * @param  \App\Models\PppProfile  $pppProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, PppProfile $pppProfile)
    {
        // Check if profile has PPP secrets
        if ($pppProfile->pppSecrets()->count() > 0) {
            return redirect()->route('ppp-profiles.show', $pppProfile->id)
                ->with('error', 'Cannot delete profile with active PPP secrets. Please delete or reassign all PPP secrets first.');
        }

        $syncWithMikrotik = $request->input('sync_with_mikrotik', false);

        // Delete from MikroTik if requested
        if ($syncWithMikrotik) {
            try {
                $this->mikrotikService->connect();
                $this->mikrotikService->deletePppProfile($pppProfile->name);
            } catch (Exception $e) {
                return redirect()->route('ppp-profiles.show', $pppProfile->id)
                    ->with('error', 'Failed to delete PPP profile from MikroTik: ' . $e->getMessage());
            }
        }

        $pppProfile->delete();

        return redirect()->route('ppp-profiles.index')
            ->with('success', 'PPP profile deleted successfully.');
    }

    /**
     * Sync all PPP profiles with MikroTik.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncAll()
    {
        try {
            $this->mikrotikService->connect();
            $profiles = PppProfile::where('is_active', true)->get();
            $mikrotikProfiles = $this->mikrotikService->getPppProfiles();
            
            // Create array of MikroTik profile names for easy lookup
            $mikrotikProfileNames = [];
            foreach ($mikrotikProfiles as $profile) {
                $mikrotikProfileNames[] = $profile['name'];
            }
            
            $created = 0;
            $updated = 0;
            $errors = [];
            
            foreach ($profiles as $profile) {
                try {
                    if (in_array($profile->name, $mikrotikProfileNames)) {
                        $this->mikrotikService->updatePppProfile($profile);
                        $updated++;
                    } else {
                        $this->mikrotikService->createPppProfile($profile);
                        $created++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to sync profile '{$profile->name}': {$e->getMessage()}";
                }
            }
            
            $message = "Sync completed: {$created} profiles created, {$updated} profiles updated.";
            
            if (count($errors) > 0) {
                $message .= " Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-profiles.index')
                    ->with('warning', $message);
            }
            
            return redirect()->route('ppp-profiles.index')
                ->with('success', $message);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-profiles.index')
                ->with('error', 'Failed to sync with MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Sync profiles from MikroTik to local database.
     *
     * @return \Illuminate\Http\Response
     */
    public function syncFromMikrotik()
    {
        return $this->import();
    }

    /**
     * Sync profile to MikroTik.
     *
     * @param  \App\Models\PppProfile  $pppProfile
     * @return \Illuminate\Http\Response
     */
    public function syncToMikrotik(PppProfile $pppProfile)
    {
        try {
            $this->mikrotikService->connect();
            
            // Check if profile exists on MikroTik
            $mikrotikProfiles = $this->mikrotikService->getPppProfiles();
            $exists = false;
            
            foreach ($mikrotikProfiles as $profile) {
                if ($profile['name'] === $pppProfile->name) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $this->mikrotikService->updatePppProfile($pppProfile);
                $message = 'PPP profile updated on MikroTik successfully.';
            } else {
                $this->mikrotikService->createPppProfile($pppProfile);
                $message = 'PPP profile created on MikroTik successfully.';
            }
            
            return redirect()->route('ppp-profiles.show', $pppProfile)
                ->with('success', $message);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-profiles.show', $pppProfile)
                ->with('error', 'Failed to sync profile to MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Import PPP profiles from MikroTik.
     *
     * @return \Illuminate\Http\Response
     */
    public function import()
    {
        try {
            $this->mikrotikService->connect();
            $mikrotikProfiles = $this->mikrotikService->getPppProfiles();
            
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            
            foreach ($mikrotikProfiles as $mikrotikProfile) {
                try {
                    $name = $mikrotikProfile['name'];
                    $profile = PppProfile::where('name', $name)->first();
                    
                    $data = [
                        'name' => $name,
                        'local_address' => $mikrotikProfile['local-address'] ?? null,
                        'remote_address' => $mikrotikProfile['remote-address'] ?? null,
                        'rate_limit' => $mikrotikProfile['rate-limit'] ?? null,
                        'parent_queue' => $mikrotikProfile['parent-queue'] ?? null,
                        'only_one' => ($mikrotikProfile['only-one'] ?? 'no') === 'yes',
                        'description' => $mikrotikProfile['comment'] ?? null,
                        'is_active' => true,
                        'price' => 0, // Default price, needs to be updated manually
                    ];
                    
                    if ($profile) {
                        // Skip default profiles
                        if (in_array($name, ['default', 'default-encryption'])) {
                            $skipped++;
                            continue;
                        }
                        
                        $profile->update($data);
                        $updated++;
                    } else {
                        PppProfile::create($data);
                        $created++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to import profile '{$mikrotikProfile['name']}': {$e->getMessage()}";
                }
            }
            
            $message = "Import completed: {$created} profiles created, {$updated} profiles updated, {$skipped} profiles skipped.";
            
            if (count($errors) > 0) {
                $message .= " Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-profiles.index')
                    ->with('warning', $message);
            }
            
            return redirect()->route('ppp-profiles.index')
                ->with('success', $message);
                
        } catch (Exception $e) {
            return redirect()->route('ppp-profiles.index')
                ->with('error', 'Failed to import from MikroTik: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete selected PPP profiles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_profiles' => 'required|array|min:1',
            'selected_profiles.*' => 'required|integer|exists:ppp_profiles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-profiles.index')
                ->with('error', 'Invalid selection. Please select valid profiles.');
        }

        $profileIds = $request->input('selected_profiles');
        $profiles = PppProfile::whereIn('id', $profileIds)->get();
        
        $deleted = 0;
        $skipped = 0;
        $errors = [];

        foreach ($profiles as $profile) {
            try {
                // Check if profile has PPP secrets
                if ($profile->pppSecrets()->count() > 0) {
                    $skipped++;
                    $errors[] = "Profile '{$profile->name}' skipped - has active PPP secrets";
                    continue;
                }

                $profile->delete();
                $deleted++;
            } catch (Exception $e) {
                $errors[] = "Failed to delete profile '{$profile->name}': {$e->getMessage()}";
            }
        }

        $message = "Bulk delete completed: {$deleted} profiles deleted";
        if ($skipped > 0) {
            $message .= ", {$skipped} profiles skipped";
        }

        if (count($errors) > 0) {
            $message .= ". Errors: " . implode("; ", $errors);
            return redirect()->route('ppp-profiles.index')
                ->with('warning', $message);
        }

        return redirect()->route('ppp-profiles.index')
            ->with('success', $message);
    }

    /**
     * Bulk sync selected PPP profiles to MikroTik.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function bulkSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'selected_profiles' => 'required|array|min:1',
            'selected_profiles.*' => 'required|integer|exists:ppp_profiles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->route('ppp-profiles.index')
                ->with('error', 'Invalid selection. Please select valid profiles.');
        }

        try {
            $this->mikrotikService->connect();
            
            $profileIds = $request->input('selected_profiles');
            $profiles = PppProfile::whereIn('id', $profileIds)->get();
            
            // Get existing MikroTik profiles
            $mikrotikProfiles = $this->mikrotikService->getPppProfiles();
            $mikrotikProfileNames = array_column($mikrotikProfiles, 'name');
            
            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($profiles as $profile) {
                try {
                    if (in_array($profile->name, $mikrotikProfileNames)) {
                        $this->mikrotikService->updatePppProfile($profile);
                        $updated++;
                    } else {
                        $this->mikrotikService->createPppProfile($profile);
                        $created++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Failed to sync profile '{$profile->name}': {$e->getMessage()}";
                }
            }

            $message = "Bulk sync completed: {$created} profiles created, {$updated} profiles updated on MikroTik";

            if (count($errors) > 0) {
                $message .= ". Errors: " . implode("; ", $errors);
                return redirect()->route('ppp-profiles.index')
                    ->with('warning', $message);
            }

            return redirect()->route('ppp-profiles.index')
                ->with('success', $message);

        } catch (Exception $e) {
            return redirect()->route('ppp-profiles.index')
                ->with('error', 'Failed to connect to MikroTik: ' . $e->getMessage());
        }
    }
}