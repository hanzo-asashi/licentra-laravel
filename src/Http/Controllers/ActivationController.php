<?php

declare(strict_types=1);

namespace Licentra\LicentraLaravel\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Licentra\LicentraLaravel\Facades\LicentraLaravel;
use Licentra\LicentraLaravel\Support\LicenseKeyValidator;

class ActivationController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var string $licenseKey */
        $licenseKey = (string) $request->input('license_key');
        if (empty($licenseKey)) {
            return back()->withErrors(['license_key' => 'Kode lisensi tidak boleh kosong.']);
        }

        $licenseKey = LicenseKeyValidator::normalize($licenseKey);

        if (! LicenseKeyValidator::isValid($licenseKey)) {
            return back()->withErrors(['license_key' => 'Format kunci lisensi tidak valid. Format yang benar: LCN-XXXX-XXXX-XXXX-XXXX']);
        }

        try {
            LicentraLaravel::setLicenseKey($licenseKey)->activate();

            return back()->with('licentra_success', 'Lisensi aplikasi berhasil diaktifkan!');
        } catch (Exception $e) {
            return back()->withErrors(['license_key' => $e->getMessage()]);
        }
    }
}
