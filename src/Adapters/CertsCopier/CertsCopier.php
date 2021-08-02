<?php

namespace It5\Adapters\CertsCopier;

use It5\DebugLibs\DebugLib;

class CertsCopier
{
    public function copyCertAndKey(
        string $certFromPath,
        string $certToPath,
        string $certPermissions,
        string $privKeyFromPath,
        string $privKeyToPath,
        string $privKeyPermissions,
    ): bool
    {
        $certResult = true;
        $privKeyResult = true;

//        DebugLib::dump($certToPath);
//        DebugLib::dump($certFromPath);
        if ($certToPath != $certFromPath) {
            $certResult = $this->copyFileRecursive($certFromPath, $certToPath, $certPermissions);
        }
//        DebugLib::dump($privKeyToPath);
//        DebugLib::dump($privKeyFromPath);
        if ($privKeyToPath != $privKeyFromPath) {
            $privKeyResult = $this->copyFileRecursive($privKeyFromPath, $privKeyToPath, $privKeyPermissions);
        }

//        DebugLib::dump('---------');

        $result = $certResult && $privKeyResult;

        return $result;
    }

    private function copyFileRecursive(string $from, string $to, string $permissions): bool
    {
        $result = true;
        try {
            if (!is_dir(dirname($to))) {
                mkdir(dirname($to), octdec($permissions), true);
                chmod(dirname($to), octdec($permissions));
            }
            copy($from, $to);
            chmod($to, octdec($permissions));
        } catch (\Exception) {
            $result = false;
        }

        return $result;
    }
}
