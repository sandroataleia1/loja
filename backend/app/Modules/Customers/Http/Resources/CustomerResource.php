<?php

declare(strict_types=1);

namespace App\Modules\Customers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hasSpouse      = feature('customer.spouse');
        $hasGuarantor   = feature('customer.guarantor');
        $hasCredit      = feature('customer.credit_analysis');
        $hasInstallment = feature('sales.installment');

        return [
            // ── Identificação ──────────────────────────────────────────────────
            'uuid'              => $this->uuid,
            'code'              => $this->code,
            'person_type'       => $this->person_type?->value,
            'person_type_label' => $this->person_type?->label(),
            'name'              => $this->name,
            'trade_name'        => $this->trade_name,
            'document'          => $this->document,
            'rg'                => $this->rg,
            'ie'                => $this->ie,
            'im'                => $this->im,
            'email'             => $this->email,
            'birth_date'        => $this->birth_date?->toDateString(),
            'gender'            => $this->gender?->value,
            'gender_label'      => $this->gender?->label(),
            'marital_status'    => $this->marital_status?->value ?? $this->civil_status,
            'marital_status_label' => $this->maritalStatusLabel(),
            'nationality'       => $this->nationality,
            'birth_city'        => $this->birth_city,
            'birth_state'       => $this->birth_state,
            'education_level'   => $this->education_level?->value,
            'education_level_label' => $this->education_level?->label(),
            'father_name'       => $this->father_name,
            'mother_name'       => $this->mother_name,

            // ── Status / situação ──────────────────────────────────────────────
            'situation'       => $this->situation,
            'status'          => $this->status?->value,
            'status_label'    => $this->status?->label(),
            'blocked_reason'  => $this->blocked_reason,
            'blocked_at'      => $this->blocked_at?->toISOString(),
            'is_active'       => $this->is_active,
            'is_default_consumer' => $this->is_default_consumer,

            // ── Classificação fiscal ───────────────────────────────────────────
            'is_final_consumer'         => $this->is_final_consumer ?? true,
            'is_free_zone'              => $this->is_free_zone ?? false,
            'is_store_chain'            => $this->is_store_chain ?? false,
            'is_public_entity'          => $this->is_public_entity ?? false,
            'withholds_pis_cofins'      => $this->withholds_pis_cofins ?? false,
            'withholds_irpj'            => $this->withholds_irpj ?? false,
            'withholds_iss'             => $this->withholds_iss ?? false,
            'iss_rate'                  => $this->iss_rate,
            'withholds_social_security' => $this->withholds_social_security ?? false,
            'calculates_icms_discount'  => $this->calculates_icms_discount ?? false,
            'discount_type'             => $this->discount_type,
            'capital_stock_cents'       => $this->capital_stock_cents,

            // ── Crédito ───────────────────────────────────────────────────────
            'credit_limit'       => $this->credit_limit,
            'credit_limit_cents' => $this->credit_limit_cents ?? 0,
            'notes'              => $this->notes,

            // ── Vínculos ──────────────────────────────────────────────────────
            'seller_id'         => $this->seller_id,
            'representative_id' => $this->representative_id,
            'carrier_id'        => $this->carrier_id,
            'price_list_id'     => $this->price_list_id,
            'collection_bank_id' => $this->collection_bank_id,
            'website'           => $this->website,
            'contact_name'      => $this->contact_name,
            'postal_box'        => $this->postal_box,
            'economic_activity' => $this->economic_activity,

            // ── Compras ───────────────────────────────────────────────────────
            'last_purchase_at'  => $this->last_purchase_at?->toISOString(),
            'total_purchases'   => $this->total_purchases,
            'total_orders'      => $this->total_orders,

            // ── Moradia / dados residenciais ───────────────────────────────────
            'years_at_address' => $this->years_at_address,
            'housing_type'     => $this->housing_type?->value,
            'housing_type_label' => $this->housing_type?->label(),
            'rent_cents'       => $this->rent_cents,

            // ── Dados profissionais ────────────────────────────────────────────
            'profession'        => $this->profession,
            'professional_card' => $this->professional_card,
            'employer'          => $this->employer,
            'employer_address'  => $this->employer_address,
            'employer_phone'    => $this->employer_phone,
            'work_start_date'   => $this->work_start_date?->toDateString(),
            'monthly_income'    => $this->monthly_income,
            'other_income'      => $this->other_income,
            'other_income_source' => $this->other_income_source,
            'total_income'      => $this->totalIncome(),

            // ── Relações carregadas ────────────────────────────────────────────
            'seller'           => $this->whenLoaded('seller'),
            'representative'   => $this->whenLoaded('representative'),
            'carrier'          => $this->whenLoaded('carrier'),
            'price_list'       => $this->whenLoaded('priceList'),
            'collection_bank'  => $this->whenLoaded('collectionBank'),
            'addresses'        => CustomerAddressResource::collection($this->whenLoaded('addresses')),
            'contacts'         => CustomerContactResource::collection($this->whenLoaded('contacts')),
            'tags'             => CustomerTagResource::collection($this->whenLoaded('tags')),
            'commercial_references' => CustomerCommercialReferenceResource::collection($this->whenLoaded('commercialReferences')),
            'purchase_references'   => CustomerPurchaseReferenceResource::collection($this->whenLoaded('purchaseReferences')),

            // ── Documentos ────────────────────────────────────────────────────
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($d) => [
                'uuid'          => $d->uuid,
                'document_type' => $d->document_type,
                'file_name'     => $d->file_name,
                'file_size_fmt' => $d->fileSizeFormatted(),
                'mime_type'     => $d->mime_type,
                'created_at'    => $d->created_at,
            ])),

            // ── Interações ────────────────────────────────────────────────────
            'interactions'       => $this->whenLoaded('interactions'),
            'interactions_count' => $this->whenLoaded('interactions', fn () => $this->interactions->count()),

            // ── Cartões ───────────────────────────────────────────────────────
            'cards' => $this->whenLoaded('cards'),

            // ── Referências bancárias ─────────────────────────────────────────
            'bank_references' => $this->whenLoaded('bankReferences'),

            // ── Custom fields ──────────────────────────────────────────────────
            'custom_fields' => $this->when(
                $this->relationLoaded('customFieldValues'),
                fn () => $this->getCustomFieldsArray(),
            ),

            // ── Cônjuge (feature-gated) ────────────────────────────────────────
            $this->mergeWhen($hasSpouse, [
                'spouse' => [
                    'name'           => $this->spouse_name,
                    'document'       => $this->spouse_document,
                    'cpf'            => $this->spouse_cpf,
                    'rg'             => $this->spouse_rg,
                    'phone'          => $this->spouse_phone,
                    'employer'       => $this->spouse_employer,
                    'income'         => $this->spouse_income,
                    'monthly_income' => $this->spouse_monthly_income,
                    'profession'     => $this->spouse_profession,
                    'birth_date'     => $this->spouse_birth_date?->toDateString(),
                    'gender'         => $this->spouse_gender,
                    'in_analysis'    => $this->spouseIncludedInAnalysis(),
                ],
            ]),

            // ── Avalistas normalizados (feature-gated) ─────────────────────────
            $this->mergeWhen($hasGuarantor, [
                'guarantors'           => $this->whenLoaded('guarantors'),
                'guarantor_name'       => $this->guarantor_name,
                'guarantor_document'   => $this->guarantor_document,
                'guarantor_phone'      => $this->guarantor_phone,
                'guarantor_address'    => $this->guarantor_address,
                'guarantor_income'     => $this->guarantor_income,
                'guarantor_profession' => $this->guarantor_profession,
                'guarantor_birth_date' => $this->guarantor_birth_date?->toDateString(),
                'guarantor_gender'     => $this->guarantor_gender,
            ]),

            // ── Dependentes autorizados (feature-gated: sales.installment) ─────
            $this->mergeWhen($hasInstallment, [
                'authorized_buyers' => $this->whenLoaded('authorizedBuyers', fn () =>
                    $this->authorizedBuyers->map(fn ($b) => array_merge(
                        $b->toArray(),
                        ['is_valid' => $b->isValid()],
                    ))
                ),
            ]),

            // ── Bens materiais (feature-gated: customer.credit_analysis) ────────
            $this->mergeWhen($hasCredit, [
                'assets' => $this->whenLoaded('assets'),
                'credit' => [
                    'monthly_income'        => $this->monthly_income,
                    'other_income'          => $this->other_income,
                    'total_income'          => $this->totalIncome(),
                    'profession'            => $this->profession,
                    'employer'              => $this->employer,
                    'employer_phone'        => $this->employer_phone,
                    'work_start_date'       => $this->work_start_date?->toDateString(),
                    'credit_score'          => $this->credit_score,
                    'credit_score_label'    => $this->creditScoreLabel(),
                    'credit_limit_cents'    => $this->credit_limit_cents ?? 0,
                    'credit_limit_used_pct' => $this->creditLimitUsedPercent(),
                    'credit_analysis_date'  => $this->credit_analysis_date?->toDateString(),
                    'credit_notes'          => $this->credit_notes,
                    'spc_status'            => $this->spc_status?->value,
                    'spc_status_label'      => $this->spc_status?->label(),
                    'spc_consulted_at'      => $this->spc_consulted_at?->toISOString(),
                    'has_income'            => $this->hasIncome(),
                ],
            ]),

            // ── Timestamps ────────────────────────────────────────────────────
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function getCustomFieldsArray(): array
    {
        return $this->customFieldValues
            ->mapWithKeys(fn ($v) => [$v->definition->field_key ?? $v->definition_id => $v->getValue()])
            ->toArray();
    }
}
