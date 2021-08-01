<?php

namespace It5\CertsCopier;

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

        if ($certToPath != $certFromPath) {
            $certResult = $this->copyFileRecursive($certFromPath, $certToPath, $certPermissions);
        }
        if ($privKeyToPath != $privKeyFromPath) {
            $privKeyResult = $this->copyFileRecursive($privKeyFromPath, $privKeyToPath, $privKeyPermissions);
        }

        $result = $certResult && $privKeyResult;

        return $result;
    }

    private function copyFileRecursive(string $from, string $to, string $permissions): bool
    {
        $result = true;
        try {
            if (!is_dir(dirname($to))) {
                mkdir(dirname($to), $permissions, true);
            }
            copy($from, $to);
            chmod($to, $permissions);
        } catch (\Exception) {
            $result = false;
        }

        return $result;
    }
}