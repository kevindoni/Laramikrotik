@extends('layouts.admin')

@section('title', 'Company Settings')

@section('main-content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Company Settings') }}</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Company Information</h6>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('company-settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="company_name">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                           id="company_name" name="company_name" value="{{ old('company_name', $settings['company_name']) }}" required>
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $settings['email']) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                   id="address" name="address" value="{{ old('address', $settings['address']) }}" required>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="city">City <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                           id="city" name="city" value="{{ old('city', $settings['city']) }}" required>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="postal_code">Postal Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                           id="postal_code" name="postal_code" value="{{ old('postal_code', $settings['postal_code']) }}" required>
                                    @error('postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $settings['phone']) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-primary mb-3">Payment Information</h6>

                        <!-- Bank Transfer Section -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 font-weight-bold">Bank Transfer</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_bank_bca" name="show_bank_bca" 
                                                       {{ $settings['show_bank_bca'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_bank_bca">
                                                    <strong>Bank BCA</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('bank_bca') is-invalid @enderror" 
                                                   id="bank_bca" name="bank_bca" value="{{ old('bank_bca', $settings['bank_bca']) }}" 
                                                   placeholder="1234567890">
                                            @error('bank_bca')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_bank_mandiri" name="show_bank_mandiri" 
                                                       {{ $settings['show_bank_mandiri'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_bank_mandiri">
                                                    <strong>Bank Mandiri</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('bank_mandiri') is-invalid @enderror" 
                                                   id="bank_mandiri" name="bank_mandiri" value="{{ old('bank_mandiri', $settings['bank_mandiri']) }}" 
                                                   placeholder="0987654321">
                                            @error('bank_mandiri')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_bank_bni" name="show_bank_bni" 
                                                       {{ $settings['show_bank_bni'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_bank_bni">
                                                    <strong>Bank BNI</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('bank_bni') is-invalid @enderror" 
                                                   id="bank_bni" name="bank_bni" value="{{ old('bank_bni', $settings['bank_bni']) }}" 
                                                   placeholder="1122334455">
                                            @error('bank_bni')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_bank_bri" name="show_bank_bri" 
                                                       {{ $settings['show_bank_bri'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_bank_bri">
                                                    <strong>Bank BRI</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('bank_bri') is-invalid @enderror" 
                                                   id="bank_bri" name="bank_bri" value="{{ old('bank_bri', $settings['bank_bri']) }}" 
                                                   placeholder="5544332211">
                                            @error('bank_bri')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="bank_account_name">Bank Account Name</label>
                                    <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror" 
                                           id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $settings['bank_account_name']) }}" 
                                           placeholder="LaraNetworks">
                                    @error('bank_account_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- E-Wallet Section -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0 font-weight-bold">E-Wallet</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_ewallet_dana" name="show_ewallet_dana" 
                                                       {{ $settings['show_ewallet_dana'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_ewallet_dana">
                                                    <strong>DANA</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('ewallet_dana') is-invalid @enderror" 
                                                   id="ewallet_dana" name="ewallet_dana" value="{{ old('ewallet_dana', $settings['ewallet_dana']) }}" 
                                                   placeholder="0812-3456-7890">
                                            @error('ewallet_dana')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_ewallet_ovo" name="show_ewallet_ovo" 
                                                       {{ $settings['show_ewallet_ovo'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_ewallet_ovo">
                                                    <strong>OVO</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('ewallet_ovo') is-invalid @enderror" 
                                                   id="ewallet_ovo" name="ewallet_ovo" value="{{ old('ewallet_ovo', $settings['ewallet_ovo']) }}" 
                                                   placeholder="0812-3456-7890">
                                            @error('ewallet_ovo')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_ewallet_gopay" name="show_ewallet_gopay" 
                                                       {{ $settings['show_ewallet_gopay'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_ewallet_gopay">
                                                    <strong>GoPay</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('ewallet_gopay') is-invalid @enderror" 
                                                   id="ewallet_gopay" name="ewallet_gopay" value="{{ old('ewallet_gopay', $settings['ewallet_gopay']) }}" 
                                                   placeholder="0812-3456-7890">
                                            @error('ewallet_gopay')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_ewallet_shopeepay" name="show_ewallet_shopeepay" 
                                                       {{ $settings['show_ewallet_shopeepay'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_ewallet_shopeepay">
                                                    <strong>ShopeePay</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('ewallet_shopeepay') is-invalid @enderror" 
                                                   id="ewallet_shopeepay" name="ewallet_shopeepay" value="{{ old('ewallet_shopeepay', $settings['ewallet_shopeepay']) }}" 
                                                   placeholder="0812-3456-7890">
                                            @error('ewallet_shopeepay')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-checkbox mb-2">
                                                <input type="checkbox" class="custom-control-input" id="show_ewallet_linkaja" name="show_ewallet_linkaja" 
                                                       {{ $settings['show_ewallet_linkaja'] ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="show_ewallet_linkaja">
                                                    <strong>LinkAja</strong>
                                                </label>
                                            </div>
                                            <input type="text" class="form-control @error('ewallet_linkaja') is-invalid @enderror" 
                                                   id="ewallet_linkaja" name="ewallet_linkaja" value="{{ old('ewallet_linkaja', $settings['ewallet_linkaja']) }}" 
                                                   placeholder="0812-3456-7890">
                                            @error('ewallet_linkaja')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Payment Information -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="m-0 font-weight-bold text-primary">Informasi Pembayaran Manual</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="show_manual_payment" name="show_manual_payment" 
                                               {{ $settings['show_manual_payment'] ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="show_manual_payment">
                                            <strong>Aktifkan Informasi Pembayaran Manual</strong>
                                        </label>
                                    </div>
                                    <textarea class="form-control @error('manual_payment_info') is-invalid @enderror" 
                                              id="manual_payment_info" name="manual_payment_info" rows="4" 
                                              placeholder="Masukkan informasi pembayaran manual jika tidak ada yang cocok atau tidak dimiliki...">{{ old('manual_payment_info', $settings['manual_payment_info']) }}</textarea>
                                    <small class="form-text text-muted">
                                        Gunakan opsi ini jika Anda memiliki metode pembayaran lain yang tidak tercantum di atas. 
                                        Contoh: Transfer ke rekening lain, pembayaran tunai, atau metode pembayaran khusus lainnya.
                                    </small>
                                    @error('manual_payment_info')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="payment_note">Payment Note</label>
                            <textarea class="form-control @error('payment_note') is-invalid @enderror" 
                                      id="payment_note" name="payment_note" rows="3">{{ old('payment_note', $settings['payment_note']) }}</textarea>
                            @error('payment_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="footer_note">Invoice Footer Note</label>
                            <textarea class="form-control @error('footer_note') is-invalid @enderror" 
                                      id="footer_note" name="footer_note" rows="3">{{ old('footer_note', $settings['footer_note']) }}</textarea>
                            @error('footer_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="developer_by">Developer By</label>
                            <input type="text" class="form-control @error('developer_by') is-invalid @enderror" 
                                   id="developer_by" name="developer_by" value="{{ old('developer_by', $settings['developer_by']) }}"
                                   placeholder="e.g., AleckRH">
                            @error('developer_by')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">This will appear in the footer as "Developer by [Name]. {{ date('Y') }}"</small>
                        </div>

                        <div class="form-group">
                            <label for="github_url">GitHub URL</label>
                            <input type="url" class="form-control @error('github_url') is-invalid @enderror" 
                                   id="github_url" name="github_url" value="{{ old('github_url', $settings['github_url']) }}"
                                   placeholder="https://github.com/kevindoni">
                            @error('github_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional: If provided, the "developer by" text will be a clickable link</small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <a href="{{ route('home') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Preview</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h5 class="font-weight-bold text-primary">{{ $settings['company_name'] }}</h5>
                        <p class="mb-1">{{ $settings['address'] }}</p>
                        <p class="mb-1">{{ $settings['city'] }}</p>
                        <p class="mb-1">Phone: {{ $settings['phone'] }}</p>
                        <p class="mb-3">Email: {{ $settings['email'] }}</p>
                    </div>

                    <hr>
                    <h6 class="font-weight-bold">Payment Instructions:</h6>
                    
                    @php
                        $hasBankTransfer = ($settings['show_bank_bca'] && $settings['bank_bca']) || 
                                          ($settings['show_bank_mandiri'] && $settings['bank_mandiri']) || 
                                          ($settings['show_bank_bni'] && $settings['bank_bni']) || 
                                          ($settings['show_bank_bri'] && $settings['bank_bri']);
                        
                        $hasEWallet = ($settings['show_ewallet_dana'] && $settings['ewallet_dana']) || 
                                     ($settings['show_ewallet_ovo'] && $settings['ewallet_ovo']) || 
                                     ($settings['show_ewallet_gopay'] && $settings['ewallet_gopay']) || 
                                     ($settings['show_ewallet_shopeepay'] && $settings['ewallet_shopeepay']) || 
                                     ($settings['show_ewallet_linkaja'] && $settings['ewallet_linkaja']);
                    @endphp
                    
                    @if($hasBankTransfer)
                    <p class="mb-2"><strong>Bank Transfer:</strong></p>
                    @if($settings['show_bank_bca'] && $settings['bank_bca'])
                    <p class="mb-1">Bank BCA: {{ $settings['bank_bca'] }}</p>
                    @endif
                    @if($settings['show_bank_mandiri'] && $settings['bank_mandiri'])
                    <p class="mb-1">Bank Mandiri: {{ $settings['bank_mandiri'] }}</p>
                    @endif
                    @if($settings['show_bank_bni'] && $settings['bank_bni'])
                    <p class="mb-1">Bank BNI: {{ $settings['bank_bni'] }}</p>
                    @endif
                    @if($settings['show_bank_bri'] && $settings['bank_bri'])
                    <p class="mb-1">Bank BRI: {{ $settings['bank_bri'] }}</p>
                    @endif
                    @if($settings['bank_account_name'])
                    <p class="mb-3">A/N: {{ $settings['bank_account_name'] }}</p>
                    @endif
                    @endif

                    @if($hasEWallet)
                    <p class="mb-2"><strong>E-Wallet:</strong></p>
                    @if($settings['show_ewallet_dana'] && $settings['ewallet_dana'])
                    <p class="mb-1">DANA: {{ $settings['ewallet_dana'] }}</p>
                    @endif
                    @if($settings['show_ewallet_ovo'] && $settings['ewallet_ovo'])
                    <p class="mb-1">OVO: {{ $settings['ewallet_ovo'] }}</p>
                    @endif
                    @if($settings['show_ewallet_gopay'] && $settings['ewallet_gopay'])
                    <p class="mb-1">GoPay: {{ $settings['ewallet_gopay'] }}</p>
                    @endif
                    @if($settings['show_ewallet_shopeepay'] && $settings['ewallet_shopeepay'])
                    <p class="mb-1">ShopeePay: {{ $settings['ewallet_shopeepay'] }}</p>
                    @endif
                    @if($settings['show_ewallet_linkaja'] && $settings['ewallet_linkaja'])
                    <p class="mb-3">LinkAja: {{ $settings['ewallet_linkaja'] }}</p>
                    @endif
                    @endif

                    @if($settings['show_manual_payment'] && $settings['manual_payment_info'])
                    <p class="mb-2"><strong>Informasi Pembayaran Manual:</strong></p>
                    <p class="mb-3">{{ $settings['manual_payment_info'] }}</p>
                    @endif

                    @if($settings['payment_note'])
                    <p class="mb-2"><strong>Note:</strong></p>
                    <p class="mb-3">{{ $settings['payment_note'] }}</p>
                    @endif

                    @if($settings['footer_note'])
                    <p class="small text-muted">{{ $settings['footer_note'] }}</p>
                    @endif
                    
                    @if($settings['developer_by'])
                    <hr>
                    <p class="small text-muted text-center">Developer by 
                        @if($settings['github_url'])
                            <a href="{{ $settings['github_url'] }}" target="_blank">{{ $settings['developer_by'] }}</a>
                        @else
                            {{ $settings['developer_by'] }}
                        @endif
                        . {{ date('Y') }}
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
