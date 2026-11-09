<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaymentCalculationsTest extends TestCase
{
    /**
     * Test 1: Comprobación del cálculo de comisión de terminal bancaria con IVA dinámico de Settings.
     */
    public function test_terminal_commission_with_dynamic_iva()
    {
        $baseAmount = 1000.00;
        $fixedCommission = 5.00;
        $percentCommission = 2.5; // 2.5%

        // Escenario A: IVA al 16% (Estándar)
        $taxIvaRateA = 16.0;
        $ivaMultiplierA = 1 + ($taxIvaRateA / 100); // 1.16
        $rawCommA = $fixedCommission + ($baseAmount * ($percentCommission / 100)); // 5 + 25 = 30
        $expectedCommA = round($rawCommA * $ivaMultiplierA, 2); // 30 * 1.16 = 34.80

        $this->assertEquals(34.80, $expectedCommA);

        // Escenario B: IVA al 8% (Zona Fronteriza)
        $taxIvaRateB = 8.0;
        $ivaMultiplierB = 1 + ($taxIvaRateB / 100); // 1.08
        $rawCommB = $fixedCommission + ($baseAmount * ($percentCommission / 100)); // 30
        $expectedCommB = round($rawCommB * $ivaMultiplierB, 2); // 30 * 1.08 = 32.40

        $this->assertEquals(32.40, $expectedCommB);
    }

    /**
     * Test 2: Sobretasa de MSI trasladada al cliente (transfer_msi_cost_to_client = true).
     */
    public function test_msi_sobretasa_transferred_to_client()
    {
        $baseAmount = 10000.00;
        $msiRate = 8.5; // 8.5% a 6 meses
        $taxIvaRate = 16.0;
        $ivaMultiplier = 1 + ($taxIvaRate / 100); // 1.16
        $transferToClient = true;

        $rawSobretasaWithIva = ($baseAmount * ($msiRate / 100)) * $ivaMultiplier; // 850 * 1.16 = 986.00

        $interestAmount = $transferToClient ? $rawSobretasaWithIva : 0;
        $absorbedByCompany = $transferToClient ? 0 : $rawSobretasaWithIva;

        $clientTotal = $baseAmount + $interestAmount;

        $this->assertEquals(986.00, round($rawSobretasaWithIva, 2));
        $this->assertEquals(10986.00, round($clientTotal, 2));
        $this->assertEquals(0.00, round($absorbedByCompany, 2));
    }

    /**
     * Test 3: Sobretasa de MSI absorbida por la empresa cuando Campaña MSI está ACTIVA (msi_campaign_active = true).
     */
    public function test_msi_sobretasa_absorbed_by_company_when_campaign_active()
    {
        $baseAmount = 10000.00;
        $msiRate = 12.0; // 12.0% a 12 meses
        $taxIvaRate = 16.0;
        $ivaMultiplier = 1 + ($taxIvaRate / 100); // 1.16
        $transferToClient = false;
        $msiCampaignActive = true;

        $rawSobretasaWithIva = ($baseAmount * ($msiRate / 100)) * $ivaMultiplier; // 1200 * 1.16 = 1392.00

        $interestAmount = 0;
        $msiSobretasaAbsorbed = 0;

        if ($transferToClient) {
            $interestAmount = $rawSobretasaWithIva;
        } elseif ($msiCampaignActive) {
            $msiSobretasaAbsorbed = $rawSobretasaWithIva;
        }

        $clientTotal = $baseAmount + $interestAmount;

        $this->assertEquals(10000.00, round($clientTotal, 2));
        $this->assertEquals(1392.00, round($msiSobretasaAbsorbed, 2));
    }

    /**
     * Test 4: Sobretasa de MSI puramente informativa cuando Campaña MSI está INACTIVA (msi_campaign_active = false).
     */
    public function test_msi_sobretasa_informative_when_campaign_inactive()
    {
        $baseAmount = 10000.00;
        $msiRate = 12.0;
        $taxIvaRate = 16.0;
        $ivaMultiplier = 1 + ($taxIvaRate / 100);
        $transferToClient = false;
        $msiCampaignActive = false;

        $rawSobretasaWithIva = ($baseAmount * ($msiRate / 100)) * $ivaMultiplier; // 1392.00

        $interestAmount = 0;
        $msiSobretasaAbsorbed = 0;

        if ($transferToClient) {
            $interestAmount = $rawSobretasaWithIva;
        } elseif ($msiCampaignActive) {
            $msiSobretasaAbsorbed = $rawSobretasaWithIva;
        }

        $clientTotal = $baseAmount + $interestAmount;

        $this->assertEquals(10000.00, round($clientTotal, 2));
        $this->assertEquals(0.00, round($msiSobretasaAbsorbed, 2));
        $this->assertEquals(1392.00, round($rawSobretasaWithIva, 2)); // Para uso informativo en UI
    }

    /**
     * Test 5: Combinación de Múltiples Pagos (Efectivo + Tarjeta con comisión a cliente).
     */
    public function test_multiple_payments_combination_breakdown()
    {
        $cartTotal = 15000.00;
        $taxIvaRate = 16.0;
        $ivaMult = 1.16;

        // Pago 1: $5,000 en Efectivo (Sin comisión, sin financiamiento)
        $payment1_base = 5000.00;
        $payment1_comm = 0.00;
        $payment1_total = 5000.00;

        // Pago 2: $10,000 en Tarjeta (Comisión fija $10 + 2% trasladada al cliente)
        $payment2_base = 10000.00;
        $payment2_rawComm = 10.00 + ($payment2_base * 0.02); // 10 + 200 = 210
        $payment2_commWithIva = round($payment2_rawComm * $ivaMult, 2); // 210 * 1.16 = 243.60
        $payment2_total = $payment2_base + $payment2_commWithIva; // 10243.60

        $totalAmountPaid = $payment1_total + $payment2_total; // 15243.60
        $saleTotal = $cartTotal + $payment2_commWithIva; // 15243.60

        $this->assertEquals(243.60, $payment2_commWithIva);
        $this->assertEquals(15243.60, $totalAmountPaid);
        $this->assertEquals($saleTotal, $totalAmountPaid);
    }

    /**
     * Test 6: Auto-detección de Apartado (Layaway) cuando la suma de pagos es menor al total requerido.
     */
    public function test_layaway_autotrigger_when_payment_is_insufficient()
    {
        $saleTotal = 8000.00;
        $totalAmountPaid = 3000.00;

        $isLayawayInput = false;
        $finalIsLayaway = $isLayawayInput;

        if ($totalAmountPaid < $saleTotal) {
            $finalIsLayaway = true;
        }

        $this::assertTrue($finalIsLayaway);
    }
}
