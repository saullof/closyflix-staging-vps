<form method="POST" action="{{ route('my.settings.taxes.save') }}">
    @csrf

    @if(session('success'))
        <div class="alert alert-success text-white font-weight-bold mt-2" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="form-group d-none">
        <label for="taxType">{{ __('Tax type') }}</label>
        <select class="form-control" id="taxType" name="taxType">
            <option value="dac7" {{ old('taxType', 'dac7') === 'dac7' ? 'selected' : '' }}>DAC7</option>
        </select>
        @if($errors->has('taxType'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('taxType') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="legalName">{{ __('Legal name') }}</label>
        <input
            class="form-control {{ $errors->has('legalName') ? 'is-invalid' : '' }}"
            id="legalName"
            name="legalName"
            value="{{ old('legalName', $userTax->legal_name ?? '') }}"
        >
        @if($errors->has('legalName'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('legalName') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="dateOfBirth">{{ __('Date of birth') }}</label>
        <input
            type="date"
            class="form-control {{ $errors->has('dateOfBirth') ? 'is-invalid' : '' }}"
            id="dateOfBirth"
            name="dateOfBirth"
            value="{{ old('dateOfBirth', $userTax && $userTax->date_of_birth ? \Carbon\Carbon::parse($userTax->date_of_birth)->format('Y-m-d') : '') }}"
        >
        @if($errors->has('dateOfBirth'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('dateOfBirth') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="issuingCountry">{{ __('ID Issuing Country') }}</label>
        <select class="form-control {{ $errors->has('issuingCountry') ? 'is-invalid' : '' }}" id="issuingCountry" name="issuingCountry">
            <option value=""></option>
            @foreach($countries as $country)
                <option
                    value="{{ $country->id }}"
                    {{ (string) old('issuingCountry', $userTax->issuing_country_id ?? '') === (string) $country->id ? 'selected' : '' }}
                >
                    {{ __($country->name) }}
                </option>
            @endforeach
        </select>
        @if($errors->has('issuingCountry'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('issuingCountry') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="taxIdentificationNumber">{{ __('Tax identification number') }}</label>
        <input
            class="form-control {{ $errors->has('taxIdentificationNumber') ? 'is-invalid' : '' }}"
            id="taxIdentificationNumber"
            name="taxIdentificationNumber"
            value="{{ old('taxIdentificationNumber', $userTax->tax_identification_number ?? '') }}"
        >
        @if($errors->has('taxIdentificationNumber'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('taxIdentificationNumber') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="vatNumber">{{ __('VAT number (optional)') }}</label>
        <input
            class="form-control {{ $errors->has('vatNumber') ? 'is-invalid' : '' }}"
            id="vatNumber"
            name="vatNumber"
            value="{{ old('vatNumber', $userTax->vat_number ?? '') }}"
        >
        @if($errors->has('vatNumber'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('vatNumber') }}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="primaryAddress">{{ __('Primary address') }}</label>
        <textarea
            class="form-control {{ $errors->has('primaryAddress') ? 'is-invalid' : '' }}"
            id="primaryAddress"
            name="primaryAddress"
            rows="2"
            spellcheck="false"
        >{{ old('primaryAddress', $userTax->primary_address ?? '') }}</textarea>
        @if($errors->has('primaryAddress'))
            <span class="invalid-feedback" role="alert">
                <strong>{{ $errors->first('primaryAddress') }}</strong>
            </span>
        @endif
    </div>

    <button class="btn btn-primary btn-block rounded mr-0" type="submit">{{ __('Save') }}</button>
</form>
