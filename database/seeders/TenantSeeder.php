<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \DB::table('saml2_tenants')->insert([
            'id'                => 1,
            'uuid'              => 'fb441f52-a65d-44f8-ac5b-bb12df182d90',
            'key'               => 'ucol',
            'idp_entity_id'     => 'https://dgre2.ucol.mx/idp/',
            'idp_login_url'     => 'https://dgre2.ucol.mx/simplesaml/saml2/idp/SSOService.php',
            'idp_logout_url'    => 'https://dgre2.ucol.mx/simplesaml/saml2/idp/SingleLogoutService.php',
            'idp_x509_cert'     => 'MIIDujCCAqICCQDs2TDJbLfRZDANBgkqhkiG9w0BAQsFADCBnjELMAkGA1UEBhMCTVgxDzANBgNVBAgMBkNvbGltYTEPMA0GA1UEBwwGQ29saW1hMR4wHAYDVQQKDBVVbml2ZXJzaWRhZCBkZSBDb2xpbWExHDAaBgNVBAsME1JlY3Vyc29zIEVkdWNhdGl2b3MxEjAQBgNVBAMMCUNFVVBST01FRDEbMBkGCSqGSIb3DQEJARYMZGdyZUB1Y29sLm14MB4XDTE1MDUyNjIzMDU1MloXDTIwMDUyNDIzMDU1MlowgZ4xCzAJBgNVBAYTAk1YMQ8wDQYDVQQIDAZDb2xpbWExDzANBgNVBAcMBkNvbGltYTEeMBwGA1UECgwVVW5pdmVyc2lkYWQgZGUgQ29saW1hMRwwGgYDVQQLDBNSZWN1cnNvcyBFZHVjYXRpdm9zMRIwEAYDVQQDDAlDRVVQUk9NRUQxGzAZBgkqhkiG9w0BCQEWDGRncmVAdWNvbC5teDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAN2RiO9lsOYlhzlpu/5ZyBZYhpRZ+HIXZapaoFc5tXvGA5XI0BtlMHvK67pSPK9BlfjVgSdLTJMAWToYRODwVuP4/mRZJMWE/bFEEfVBMS8y8NoY86apJyQJH7935wbvflwVq+y7pgZ3gU1DygPTI51beHnZgjDkvw/oHW3yp8PqnQIZvYc0efEW+CrIrz7xN5udjPkX9rM4UcbwZDBJ6ltZcaaTH7SmAbaRJ2I7c3QPhLd65BRGClEHEbTzK0ZQaxiPqLp8qghRabAdbci0cgQGQhXog+pIH5DhvPmDedcaNfquBXXc0/7/t7UzpS/IbvIhrg3pPMSoOsXNOYIq1X8CAwEAATANBgkqhkiG9w0BAQsFAAOCAQEAu6gtdFCsyAwcad3+62g4KTv4f22NwPiAlQciNtZR7U0TBSrfJB9HbdK1y6F/pOOn39LYdlR2MyAkYqiP6GPA41rzo8XJcUjoc6DfraaNJySx32dEF+dfSvmZFxBLJ8cjXjubQ0uBMb59MuMzAnqJtibxkoMn79dIIELAYXI1N1ImgwUmOwz/y8Lb/5jYbE2S0ehTpsZSt1j5AeS5i+7IHPrXyt4rxjE7Z+f/PdlV9D8oIs8EvXurfZWXsQGoV7PH8ODZHql3KSFoCulqwrlmj5rEbj+HeGcj63PlESaF+VbmINknrBX/kJevNvqoe/JTY+ooHBUag8o4yYtpCCEcYw==',
            'metadata'          => '[]',
            'created_at'        => now(),
            'updated_at'        => now(),
            'deleted_at'        => null,
            'relay_state_url'   => null,
            'name_id_format'    => 'persistent'
        ]);
    }
}
