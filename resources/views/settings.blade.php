@extends('layouts.app')

@section('title', __('Settings') . ' - ' . __('Phishing awareness training program'))

@section('main-content')

    <div class="main-content app-content">
        <div class="container">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
                <h1 class="page-title fw-semibold fs-18 mb-0">{{ __('Settings') }}</h1>
                <div class="ms-md-1 ms-0">
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('Dashboard') }}</a></li>
                            <li class="breadcrumb-item active" aria-current="page">{{ __('Settings') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!-- Page Header Close -->

            <!-- Start::row-1 -->
            <div class="row mb-5">
                <div class="col-xl-4">
                    <div class="card custom-card">
                        <div class="platform-info">

                            <img src="{{ $companyLogoDark }}" alt="logo" class="desktop-logo" width="250"
                                style="margin-bottom: 20px;">

                            <h5>{{ __('Platform Super Admin') }}</h5>
                            <p>{{ $all_settings->email }}</p>
                        </div>
                        <div>
                            <div class="p-4 border-bottom border-block-end-dashed">
                                <div class="mb-4">
                                    <p class="fs-15 mb-2 fw-semibold">{{ __('Company ID:') }}</p>
                                    <p class="fs-12 op-7 mb-0">{{ $all_settings->company_id }}</p>
                                </div>
                                <div class="mb-4">
                                    <p class="fs-15 mb-2 fw-semibold">{{ __('Data Storage Location:') }}</p>
                                    <p class="fs-12 op-7 mb-0">
                                        {{ $all_settings->storage_region }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-8">
                    <div class="card custom-card">
                        <div class="card-header d-sm-flex d-block">
                            <ul class="nav nav-tabs nav-tabs-header mb-0 d-sm-flex d-block" role="tablist">
                                <li class="nav-item m-1" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#personal-info" aria-selected="true">{{ __('Personal Information') }}</a>
                                </li>
                                <li class="nav-item m-1" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#platformManage" aria-selected="false"
                                        tabindex="-1">{{ __('Platform Management') }}</a>
                                </li>
                                <li class="nav-item m-1" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#siem" aria-selected="false"
                                        tabindex="-1">{{ __('SIEM Integration') }}</a>
                                </li>
                                <li class="nav-item m-1" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page"
                                        href="#whiteLabel" aria-selected="false"
                                        tabindex="-1">{{ __('White Labelling') }}</a>
                                </li>

                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="personal-info" role="tabpanel">
                                    <div class="p-sm-3 p-0">

                                        <div class="row gy-4 mb-4">
                                            <div class="col-xl-12">
                                                <label for="first-name" class="form-label">{{ __('Full Name') }}</label>
                                                <input type="text" class="form-control" id="fullName"
                                                    value="{{ $all_settings->full_name }}"
                                                    placeholder="{{ __('Full Name') }}" disabled>
                                            </div>

                                        </div>
                                        <div class="row gy-4 mb-4">
                                            <div class="col-xl-12">
                                                <label for="email-address"
                                                    class="form-label">{{ __('Email Address') }}</label>
                                                <input type="email" class="form-control" id="emailAdd"
                                                    value="{{ $all_settings->email }}"
                                                    placeholder="{{ __('Company Email') }}" disabled>
                                            </div>

                                        </div>

                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="Contact-Details"
                                                    class="form-label">{{ __('Company Name:') }}</label>
                                                <input type="text" class="form-control" id="compName"
                                                    value="{{ $all_settings->company_name }}" id="Contact-Details"
                                                    placeholder="{{ __('Company name') }}" disabled>
                                            </div>
                                        </div>

                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="Contact-Details"
                                                    class="form-label">{{ __('Country') }}</label>
                                                <select id="countryInput" class="form-control form-control-line">
                                                    <option value="">{{ __('Select country') }}</option>
                                                    <option value="AF">Afghanistan</option>
                                                    <option value="AX">Åland Islands</option>
                                                    <option value="AL">Albania</option>
                                                    <option value="DZ">Algeria</option>
                                                    <option value="AS">American Samoa</option>
                                                    <option value="AD">Andorra</option>
                                                    <option value="AO">Angola</option>
                                                    <option value="AI">Anguilla</option>
                                                    <option value="AQ">Antarctica</option>
                                                    <option value="AG">Antigua and Barbuda</option>
                                                    <option value="AR">Argentina</option>
                                                    <option value="AM">Armenia</option>
                                                    <option value="AW">Aruba</option>
                                                    <option value="AU">Australia</option>
                                                    <option value="AT">Austria</option>
                                                    <option value="AZ">Azerbaijan</option>
                                                    <option value="BS">Bahamas</option>
                                                    <option value="BH">Bahrain</option>
                                                    <option value="BD">Bangladesh</option>
                                                    <option value="BB">Barbados</option>
                                                    <option value="BY">Belarus</option>
                                                    <option value="BE">Belgium</option>
                                                    <option value="BZ">Belize</option>
                                                    <option value="BJ">Benin</option>
                                                    <option value="BM">Bermuda</option>
                                                    <option value="BT">Bhutan</option>
                                                    <option value="BO">Bolivia, Plurinational State of</option>
                                                    <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
                                                    <option value="BA">Bosnia and Herzegovina</option>
                                                    <option value="BW">Botswana</option>
                                                    <option value="BV">Bouvet Island</option>
                                                    <option value="BR">Brazil</option>
                                                    <option value="IO">British Indian Ocean Territory</option>
                                                    <option value="BN">Brunei Darussalam</option>
                                                    <option value="BG">Bulgaria</option>
                                                    <option value="BF">Burkina Faso</option>
                                                    <option value="BI">Burundi</option>
                                                    <option value="KH">Cambodia</option>
                                                    <option value="CM">Cameroon</option>
                                                    <option value="CA">Canada</option>
                                                    <option value="CV">Cape Verde</option>
                                                    <option value="KY">Cayman Islands</option>
                                                    <option value="CF">Central African Republic</option>
                                                    <option value="TD">Chad</option>
                                                    <option value="CL">Chile</option>
                                                    <option value="CN">China</option>
                                                    <option value="CX">Christmas Island</option>
                                                    <option value="CC">Cocos (Keeling) Islands</option>
                                                    <option value="CO">Colombia</option>
                                                    <option value="KM">Comoros</option>
                                                    <option value="CG">Congo</option>
                                                    <option value="CD">Congo, the Democratic Republic of the</option>
                                                    <option value="CK">Cook Islands</option>
                                                    <option value="CR">Costa Rica</option>
                                                    <option value="CI">Côte d'Ivoire</option>
                                                    <option value="HR">Croatia</option>
                                                    <option value="CU">Cuba</option>
                                                    <option value="CW">Curaçao</option>
                                                    <option value="CY">Cyprus</option>
                                                    <option value="CZ">Czech Republic</option>
                                                    <option value="DK">Denmark</option>
                                                    <option value="DJ">Djibouti</option>
                                                    <option value="DM">Dominica</option>
                                                    <option value="DO">Dominican Republic</option>
                                                    <option value="EC">Ecuador</option>
                                                    <option value="EG">Egypt</option>
                                                    <option value="SV">El Salvador</option>
                                                    <option value="GQ">Equatorial Guinea</option>
                                                    <option value="ER">Eritrea</option>
                                                    <option value="EE">Estonia</option>
                                                    <option value="ET">Ethiopia</option>
                                                    <option value="FK">Falkland Islands (Malvinas)</option>
                                                    <option value="FO">Faroe Islands</option>
                                                    <option value="FJ">Fiji</option>
                                                    <option value="FI">Finland</option>
                                                    <option value="FR">France</option>
                                                    <option value="GF">French Guiana</option>
                                                    <option value="PF">French Polynesia</option>
                                                    <option value="TF">French Southern Territories</option>
                                                    <option value="GA">Gabon</option>
                                                    <option value="GM">Gambia</option>
                                                    <option value="GE">Georgia</option>
                                                    <option value="DE">Germany</option>
                                                    <option value="GH">Ghana</option>
                                                    <option value="GI">Gibraltar</option>
                                                    <option value="GR">Greece</option>
                                                    <option value="GL">Greenland</option>
                                                    <option value="GD">Grenada</option>
                                                    <option value="GP">Guadeloupe</option>
                                                    <option value="GU">Guam</option>
                                                    <option value="GT">Guatemala</option>
                                                    <option value="GG">Guernsey</option>
                                                    <option value="GN">Guinea</option>
                                                    <option value="GW">Guinea-Bissau</option>
                                                    <option value="GY">Guyana</option>
                                                    <option value="HT">Haiti</option>
                                                    <option value="HM">Heard Island and McDonald Islands</option>
                                                    <option value="VA">Holy See (Vatican City State)</option>
                                                    <option value="HN">Honduras</option>
                                                    <option value="HK">Hong Kong</option>
                                                    <option value="HU">Hungary</option>
                                                    <option value="IS">Iceland</option>
                                                    <option value="IN">India</option>
                                                    <option value="ID">Indonesia</option>
                                                    <option value="IR">Iran, Islamic Republic of</option>
                                                    <option value="IQ">Iraq</option>
                                                    <option value="IE">Ireland</option>
                                                    <option value="IM">Isle of Man</option>
                                                    <option value="IL">Israel</option>
                                                    <option value="IT">Italy</option>
                                                    <option value="JM">Jamaica</option>
                                                    <option value="JP">Japan</option>
                                                    <option value="JE">Jersey</option>
                                                    <option value="JO">Jordan</option>
                                                    <option value="KZ">Kazakhstan</option>
                                                    <option value="KE">Kenya</option>
                                                    <option value="KI">Kiribati</option>
                                                    <option value="KP">Korea, Democratic People's Republic of</option>
                                                    <option value="KR">Korea, Republic of</option>
                                                    <option value="KW">Kuwait</option>
                                                    <option value="KG">Kyrgyzstan</option>
                                                    <option value="LA">Lao People's Democratic Republic</option>
                                                    <option value="LV">Latvia</option>
                                                    <option value="LB">Lebanon</option>
                                                    <option value="LS">Lesotho</option>
                                                    <option value="LR">Liberia</option>
                                                    <option value="LY">Libya</option>
                                                    <option value="LI">Liechtenstein</option>
                                                    <option value="LT">Lithuania</option>
                                                    <option value="LU">Luxembourg</option>
                                                    <option value="MO">Macao</option>
                                                    <option value="MK">Macedonia, the former Yugoslav Republic of
                                                    </option>
                                                    <option value="MG">Madagascar</option>
                                                    <option value="MW">Malawi</option>
                                                    <option value="MY">Malaysia</option>
                                                    <option value="MV">Maldives</option>
                                                    <option value="ML">Mali</option>
                                                    <option value="MT">Malta</option>
                                                    <option value="MH">Marshall Islands</option>
                                                    <option value="MQ">Martinique</option>
                                                    <option value="MR">Mauritania</option>
                                                    <option value="MU">Mauritius</option>
                                                    <option value="YT">Mayotte</option>
                                                    <option value="MX">Mexico</option>
                                                    <option value="FM">Micronesia, Federated States of</option>
                                                    <option value="MD">Moldova, Republic of</option>
                                                    <option value="MC">Monaco</option>
                                                    <option value="MN">Mongolia</option>
                                                    <option value="ME">Montenegro</option>
                                                    <option value="MS">Montserrat</option>
                                                    <option value="MA">Morocco</option>
                                                    <option value="MZ">Mozambique</option>
                                                    <option value="MM">Myanmar</option>
                                                    <option value="NA">Namibia</option>
                                                    <option value="NR">Nauru</option>
                                                    <option value="NP">Nepal</option>
                                                    <option value="NL">Netherlands</option>
                                                    <option value="NC">New Caledonia</option>
                                                    <option value="NZ">New Zealand</option>
                                                    <option value="NI">Nicaragua</option>
                                                    <option value="NE">Niger</option>
                                                    <option value="NG">Nigeria</option>
                                                    <option value="NU">Niue</option>
                                                    <option value="NF">Norfolk Island</option>
                                                    <option value="MP">Northern Mariana Islands</option>
                                                    <option value="NO">Norway</option>
                                                    <option value="OM">Oman</option>
                                                    <option value="PK">Pakistan</option>
                                                    <option value="PW">Palau</option>
                                                    <option value="PS">Palestinian Territory, Occupied</option>
                                                    <option value="PA">Panama</option>
                                                    <option value="PG">Papua New Guinea</option>
                                                    <option value="PY">Paraguay</option>
                                                    <option value="PE">Peru</option>
                                                    <option value="PH">Philippines</option>
                                                    <option value="PN">Pitcairn</option>
                                                    <option value="PL">Poland</option>
                                                    <option value="PT">Portugal</option>
                                                    <option value="PR">Puerto Rico</option>
                                                    <option value="QA">Qatar</option>
                                                    <option value="RE">Réunion</option>
                                                    <option value="RO">Romania</option>
                                                    <option value="RU">Russian Federation</option>
                                                    <option value="RW">Rwanda</option>
                                                    <option value="BL">Saint Barthélemy</option>
                                                    <option value="SH">Saint Helena, Ascension and Tristan da Cunha
                                                    </option>
                                                    <option value="KN">Saint Kitts and Nevis</option>
                                                    <option value="LC">Saint Lucia</option>
                                                    <option value="MF">Saint Martin (French part)</option>
                                                    <option value="PM">Saint Pierre and Miquelon</option>
                                                    <option value="VC">Saint Vincent and the Grenadines</option>
                                                    <option value="WS">Samoa</option>
                                                    <option value="SM">San Marino</option>
                                                    <option value="ST">Sao Tome and Principe</option>
                                                    <option value="SA">Saudi Arabia</option>
                                                    <option value="SN">Senegal</option>
                                                    <option value="RS">Serbia</option>
                                                    <option value="SC">Seychelles</option>
                                                    <option value="SL">Sierra Leone</option>
                                                    <option value="SG">Singapore</option>
                                                    <option value="SX">Sint Maarten (Dutch part)</option>
                                                    <option value="SK">Slovakia</option>
                                                    <option value="SI">Slovenia</option>
                                                    <option value="SB">Solomon Islands</option>
                                                    <option value="SO">Somalia</option>
                                                    <option value="ZA">South Africa</option>
                                                    <option value="GS">South Georgia and the South Sandwich Islands
                                                    </option>
                                                    <option value="SS">South Sudan</option>
                                                    <option value="ES">Spain</option>
                                                    <option value="LK">Sri Lanka</option>
                                                    <option value="SD">Sudan</option>
                                                    <option value="SR">Suriname</option>
                                                    <option value="SJ">Svalbard and Jan Mayen</option>
                                                    <option value="SZ">Swaziland</option>
                                                    <option value="SE">Sweden</option>
                                                    <option value="CH">Switzerland</option>
                                                    <option value="SY">Syrian Arab Republic</option>
                                                    <option value="TW">Taiwan, Province of China</option>
                                                    <option value="TJ">Tajikistan</option>
                                                    <option value="TZ">Tanzania, United Republic of</option>
                                                    <option value="TH">Thailand</option>
                                                    <option value="TL">Timor-Leste</option>
                                                    <option value="TG">Togo</option>
                                                    <option value="TK">Tokelau</option>
                                                    <option value="TO">Tonga</option>
                                                    <option value="TT">Trinidad and Tobago</option>
                                                    <option value="TN">Tunisia</option>
                                                    <option value="TR">Turkey</option>
                                                    <option value="TM">Turkmenistan</option>
                                                    <option value="TC">Turks and Caicos Islands</option>
                                                    <option value="TV">Tuvalu</option>
                                                    <option value="UG">Uganda</option>
                                                    <option value="UA">Ukraine</option>
                                                    <option value="AE">United Arab Emirates</option>
                                                    <option value="GB">United Kingdom</option>
                                                    <option value="US">United States</option>
                                                    <option value="UM">United States Minor Outlying Islands</option>
                                                    <option value="UY">Uruguay</option>
                                                    <option value="UZ">Uzbekistan</option>
                                                    <option value="VU">Vanuatu</option>
                                                    <option value="VE">Venezuela, Bolivarian Republic of</option>
                                                    <option value="VN">Viet Nam</option>
                                                    <option value="VG">Virgin Islands, British</option>
                                                    <option value="VI">Virgin Islands, U.S.</option>
                                                    <option value="WF">Wallis and Futuna</option>
                                                    <option value="EH">Western Sahara</option>
                                                    <option value="YE">Yemen</option>
                                                    <option value="ZM">Zambia</option>
                                                    <option value="ZW">Zimbabwe</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="Contact-Details"
                                                    class="form-label">{{ __('Time Zone') }}</label>
                                                <select class="form-control form-control-line" id="timeZoneInput">
                                                    <option value="Etc/Greenwich">(GMT+00:00)
                                                        {{ __('Greenwich Mean Time : Dublin, Edinburgh, Lisbon, London') }}
                                                    </option>
                                                    <option value="Etc/GMT+12">(GMT-12:00)
                                                        {{ __('International Date Line West') }}
                                                    </option>
                                                    <option value="Pacific/Midway">(GMT-11:00)
                                                        {{ __('Midway Island, Samoa') }}
                                                    </option>
                                                    <option value="Pacific/Honolulu">(GMT-10:00) {{ __('Hawaii') }}
                                                    </option>
                                                    <option value="US/Alaska">(GMT-09:00) {{ __('Alaska') }}</option>
                                                    <option value="America/Los_Angeles">(GMT-08:00)
                                                        {{ __('Pacific Time') }} (US &amp;
                                                        Canada)</option>
                                                    <option value="America/Tijuana">(GMT-08:00)
                                                        {{ __('Tijuana, Baja California') }}
                                                    </option>
                                                    <option value="US/Arizona">(GMT-07:00) {{ __('Arizona') }}</option>
                                                    <option value="America/Chihuahua">(GMT-07:00)
                                                        {{ __('Chihuahua, La Paz,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Mazatlan') }}
                                                    </option>
                                                    <option value="US/Mountain">(GMT-07:00) {{ __('Mountain Time') }} (US
                                                        &amp; Canada)
                                                    </option>
                                                    <option value="America/Managua">(GMT-06:00)
                                                        {{ __('Central America') }}</option>
                                                    <option value="US/Central">(GMT-06:00) {{ __('Central Time') }} (US
                                                        &amp; Canada)
                                                    </option>
                                                    <option value="America/Mexico_City">(GMT-06:00)
                                                        {{ __('Guadalajara, Mexico
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                City, Monterrey') }}
                                                    </option>
                                                    <option value="Canada/Saskatchewan">(GMT-06:00)
                                                        {{ __('Saskatchewan') }}</option>
                                                    <option value="America/Bogota">(GMT-05:00)
                                                        {{ __('Bogota, Lima, Quito, Rio
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Branco') }}
                                                    </option>
                                                    <option value="US/Eastern">(GMT-05:00) {{ __('Eastern Time') }} (US
                                                        &amp; Canada)
                                                    </option>
                                                    <option value="US/East-Indiana">(GMT-05:00) {{ __('Indiana (East)') }}
                                                    </option>
                                                    <option value="Canada/Atlantic">(GMT-04:00)
                                                        {{ __('Atlantic Time (Canada)') }}
                                                    </option>
                                                    <option value="America/Caracas">(GMT-04:00)
                                                        {{ __('Caracas, La Paz') }}</option>
                                                    <option value="America/Manaus">(GMT-04:00) {{ __('Manaus') }}
                                                    </option>
                                                    <option value="America/Santiago">(GMT-04:00) {{ __('Santiago') }}
                                                    </option>
                                                    <option value="Canada/Newfoundland">(GMT-03:30)
                                                        {{ __('Newfoundland') }}</option>
                                                    <option value="America/Sao_Paulo">(GMT-03:00) {{ __('Brasilia') }}
                                                    </option>
                                                    <option value="America/Argentina/Buenos_Aires">(GMT-03:00)
                                                        {{ __('Buenos
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Aires, Georgetown') }}
                                                    </option>
                                                    <option value="America/Godthab">(GMT-03:00) {{ __('Greenland') }}
                                                    </option>
                                                    <option value="America/Montevideo">(GMT-03:00) {{ __('Montevideo') }}
                                                    </option>
                                                    <option value="America/Noronha">(GMT-02:00) {{ __('Mid-Atlantic') }}
                                                    </option>
                                                    <option value="Atlantic/Cape_Verde">(GMT-01:00)
                                                        {{ __('Cape Verde Is.') }}</option>
                                                    <option value="Atlantic/Azores">(GMT-01:00) {{ __('Azores') }}
                                                    </option>
                                                    <option value="Africa/Casablanca">(GMT+00:00)
                                                        {{ __('Casablanca, Monrovia,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Reykjavik') }}
                                                    </option>
                                                    <option value="Etc/Greenwich">(GMT+00:00)
                                                        {{ __('Greenwich Mean Time : Dublin,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Edinburgh, Lisbon, London') }}
                                                    </option>
                                                    <option value="Europe/Amsterdam">(GMT+01:00)
                                                        {{ __('Amsterdam, Berlin, Bern,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Rome, Stockholm, Vienna') }}
                                                    </option>
                                                    <option value="Europe/Belgrade">(GMT+01:00)
                                                        {{ __('Belgrade, Bratislava,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Budapest, Ljubljana, Prague') }}
                                                    </option>
                                                    <option value="Europe/Brussels">(GMT+01:00)
                                                        {{ __('Brussels, Copenhagen,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Madrid, Paris') }}
                                                    </option>
                                                    <option value="Europe/Sarajevo">(GMT+01:00)
                                                        {{ __('Sarajevo, Skopje, Warsaw,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Zagreb') }}
                                                    </option>
                                                    <option value="Africa/Lagos">(GMT+01:00)
                                                        {{ __('West Central Africa') }}</option>
                                                    <option value="Asia/Amman">(GMT+02:00) {{ __('Amman') }}</option>
                                                    <option value="Europe/Athens">(GMT+02:00)
                                                        {{ __('Athens, Bucharest, Istanbul') }}
                                                    </option>
                                                    <option value="Asia/Beirut">(GMT+02:00) {{ __('Beirut') }}</option>
                                                    <option value="Africa/Cairo">(GMT+02:00) {{ __('Cairo') }}</option>
                                                    <option value="Africa/Harare">(GMT+02:00) {{ __('Harare, Pretoria') }}
                                                    </option>
                                                    <option value="Europe/Helsinki">(GMT+02:00)
                                                        {{ __('Helsinki, Kyiv, Riga,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Sofia, Tallinn, Vilnius') }}
                                                    </option>
                                                    <option value="Asia/Jerusalem">(GMT+02:00) {{ __('Jerusalem') }}
                                                    </option>
                                                    <option value="Europe/Minsk">(GMT+02:00) {{ __('Minsk') }}</option>
                                                    <option value="Africa/Windhoek">(GMT+02:00) {{ __('Windhoek') }}
                                                    </option>
                                                    <option value="Asia/Kuwait">(GMT+03:00)
                                                        {{ __('Kuwait, Riyadh, Baghdad') }}
                                                    </option>
                                                    <option value="Europe/Moscow">(GMT+03:00)
                                                        {{ __('Moscow, St. Petersburg,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Volgograd') }}
                                                    </option>
                                                    <option value="Africa/Nairobi">(GMT+03:00) {{ __('Nairobi') }}
                                                    </option>
                                                    <option value="Asia/Tbilisi">(GMT+03:00) {{ __('Tbilisi') }}</option>
                                                    <option value="Asia/Tehran">(GMT+03:30) {{ __('Tehran') }}</option>
                                                    <option value="Asia/Muscat">(GMT+04:00) {{ __('Abu Dhabi, Muscat') }}
                                                    </option>
                                                    <option value="Asia/Baku">(GMT+04:00) {{ __('Baku') }}</option>
                                                    <option value="Asia/Yerevan">(GMT+04:00) {{ __('Yerevan') }}</option>
                                                    <option value="Asia/Kabul">(GMT+04:30) {{ __('Kabul') }}</option>
                                                    <option value="Asia/Yekaterinburg">(GMT+05:00)
                                                        {{ __('Yekaterinburg') }}</option>
                                                    <option value="Asia/Karachi">(GMT+05:00)
                                                        {{ __('Islamabad, Karachi, Tashkent') }}
                                                    </option>
                                                    <option value="Asia/Calcutta">(GMT+05:30)
                                                        {{ __('Chennai, Kolkata, Mumbai, New
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Delhi') }}
                                                    </option>
                                                    <option value="Asia/Calcutta">(GMT+05:30)
                                                        {{ __('Sri Jayawardenapura') }}</option>
                                                    <option value="Asia/Katmandu">(GMT+05:45) {{ __('Kathmandu') }}
                                                    </option>
                                                    <option value="Asia/Almaty">(GMT+06:00)
                                                        {{ __('Almaty, Novosibirsk') }}</option>
                                                    <option value="Asia/Dhaka">(GMT+06:00) {{ __('Astana, Dhaka') }}
                                                    </option>
                                                    <option value="Asia/Rangoon">(GMT+06:30) {{ __('Yangon (Rangoon)') }}
                                                    </option>
                                                    <option value="Asia/Bangkok">(GMT+07:00)
                                                        {{ __('Bangkok, Hanoi, Jakarta') }}
                                                    </option>
                                                    <option value="Asia/Krasnoyarsk">(GMT+07:00) {{ __('Krasnoyarsk') }}
                                                    </option>
                                                    <option value="Asia/Hong_Kong">(GMT+08:00)
                                                        {{ __('Beijing, Chongqing, Hong
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Kong, Urumqi') }}
                                                    </option>
                                                    <option value="Asia/Kuala_Lumpur">(GMT+08:00)
                                                        {{ __('Kuala Lumpur, Singapore') }}
                                                    </option>
                                                    <option value="Asia/Irkutsk">(GMT+08:00)
                                                        {{ __('Irkutsk, Ulaan Bataar') }}</option>
                                                    <option value="Australia/Perth">(GMT+08:00) {{ __('Perth') }}
                                                    </option>
                                                    <option value="Asia/Taipei">(GMT+08:00) {{ __('Taipei') }}</option>
                                                    <option value="Asia/Tokyo">(GMT+09:00)
                                                        {{ __('Osaka, Sapporo, Tokyo') }}</option>
                                                    <option value="Asia/Seoul">(GMT+09:00) {{ __('Seoul') }}</option>
                                                    <option value="Asia/Yakutsk">(GMT+09:00) {{ __('Yakutsk') }}</option>
                                                    <option value="Australia/Adelaide">(GMT+09:30) {{ __('Adelaide') }}
                                                    </option>
                                                    <option value="Australia/Darwin">(GMT+09:30) {{ __('Darwin') }}
                                                    </option>
                                                    <option value="Australia/Brisbane">(GMT+10:00) {{ __('Brisbane') }}
                                                    </option>
                                                    <option value="Australia/Canberra">(GMT+10:00)
                                                        {{ __('Canberra, Melbourne,
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Sydney') }}
                                                    </option>
                                                    <option value="Australia/Hobart">(GMT+10:00) {{ __('Hobart') }}
                                                    </option>
                                                    <option value="Pacific/Guam">(GMT+10:00)
                                                        {{ __('Guam, Port Moresby') }}</option>
                                                    <option value="Asia/Vladivostok">(GMT+10:00) {{ __('Vladivostok') }}
                                                    </option>
                                                    <option value="Asia/Magadan">(GMT+11:00)
                                                        {{ __('Magadan, Solomon Is., New
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                Caledonia') }}
                                                    </option>
                                                    <option value="Pacific/Auckland">(GMT+12:00)
                                                        {{ __('Auckland, Wellington') }}
                                                    </option>
                                                    <option value="Pacific/Fiji">(GMT+12:00)
                                                        {{ __('Fiji, Kamchatka, Marshall Is.') }}
                                                    </option>
                                                    <option value="Pacific/Tongatapu">(GMT+13:00) {{ __("Nuku'alofa") }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="Contact-Details"
                                                    class="form-label">{{ __('Date Format') }}</label>
                                                <select class="form-control form-control-line" id="dateFormatInput">
                                                    <option value="dd/MM/yyyy">dd/mm/yyyy</option>
                                                    <option value="MM/dd/yyyy">mm/dd/yyyy</option>
                                                    <option value="yyyy/MM/dd">yyyy/mm/dd</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="card-footer mb-3" style="margin-bottom: 53px !important;">
                                            <div class="float-end">
                                                <button class="btn btn-primary m-1" id="updateProfileBtn">
                                                    {{ __('Update Profile') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="accordion accordion-primary my-3" id="chgPassAccor">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="headingPrimaryOne">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseCngPassword"
                                                        aria-expanded="false" aria-controls="collapseCngPassword">
                                                        {{ __('Change Password') }}
                                                    </button>
                                                </h2>
                                                <div id="collapseCngPassword" class="accordion-collapse collapse"
                                                    aria-labelledby="headingPrimaryOne" data-bs-parent="#chgPassAccor">
                                                    <div class="accordion-body">
                                                        <div>
                                                            <p class="fs-14 mb-1 fw-semibold">{{ __('Reset Password') }}
                                                            </p>
                                                            <p class="fs-12 text-muted">
                                                                {{ __('Password should be min of') }} <b
                                                                    class="text-success">8
                                                                    {{ __('digits') }}<sup>*</sup></b>{{ __(',atleast') }}
                                                                <b
                                                                    class="text-success">{{ __('One Capital letter') }}<sup>*</sup></b>
                                                                {{ __('and') }} <b
                                                                    class="text-success">{{ __('One Special Character') }}<sup>*</sup></b>
                                                                {{ __('included.') }}
                                                            </p>
                                                            <div class="mb-2">
                                                                <label for="current-password"
                                                                    class="form-label">{{ __('Current Password') }}</label>
                                                                <input type="password" class="form-control"
                                                                    id="currentPassword"
                                                                    placeholder="{{ __('Current Password') }}">
                                                            </div>
                                                            <div class="mb-2">
                                                                <label for="new-password"
                                                                    class="form-label">{{ __('New Password') }}</label>
                                                                <input type="password" class="form-control"
                                                                    id="newPassword"
                                                                    placeholder="{{ __('New Password') }}">
                                                            </div>
                                                            <div class="mb-0">
                                                                <label for="confirm-password"
                                                                    class="form-label">{{ __('Confirm Password') }}</label>
                                                                <input type="password" class="form-control"
                                                                    id="confirmPassword"
                                                                    placeholder="{{ __('Confirm Password') }}">
                                                            </div>
                                                            <div class="card-footer p-2 text-end">
                                                                <button class="btn btn-primary m-1" id="updatePassBtn">
                                                                    {{ __('Update Password') }}
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion accordion-primary my-3" id="mfaAccor">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseCngMFA"
                                                        aria-expanded="false" aria-controls="collapseCngPassword">
                                                        {{ __('Account Multi-Factor Authentication') }}
                                                    </button>
                                                </h2>
                                                <div id="collapseCngMFA" class="accordion-collapse collapse"
                                                    aria-labelledby="headingPrimaryOne" data-bs-parent="#mfaAccor">
                                                    <div class="accordion-body">
                                                        <div class="d-flex justify-content-between">
                                                            <p class="fs-14 mb-1 fw-semibold">
                                                                {{ __('Enable Multi-Factor Authentication on your Account?') }}
                                                                <i class="bx bx-info-circle p-2" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-original-title="MFA is provided by a timed-based one-time password (TOTP) utilising Google Authenticator. You will be prompted to setup MFA during your next login."
                                                                    aria-describedby="tooltip636824"></i>
                                                            </p>
                                                            <div
                                                                class="custom-toggle-switch d-flex align-items-center mb-4">
                                                                <input id="mfaSwitch" onchange="mfaChange(this)"
                                                                    name="toggleswitch001" type="checkbox"
                                                                    @if ($all_settings->company_settings->mfa == 1) checked @endif>
                                                                <label for="mfaSwitch" class="label-success"></label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion accordion-primary my-3" id="AccDeactivation">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapseAccDe"
                                                        aria-expanded="false" aria-controls="collapseCngMFA">
                                                        {{ __('Account Deactivation') }}
                                                    </button>
                                                </h2>
                                                <div id="collapseAccDe" class="accordion-collapse collapse"
                                                    aria-labelledby="headingPrimaryOne" data-bs-parent="#AccDeactivation">
                                                    <div class="accordion-body">
                                                        <div class="d-flex justify-content-between">
                                                            <p class="fs-14 mb-1 fw-semibold">
                                                                {{ __('Deactivate your Account?') }}
                                                                <i class="bx bx-info-circle p-2" data-bs-toggle="tooltip"
                                                                    data-bs-placement="top"
                                                                    data-bs-original-title="The team will be notified to deactivate this account. Deactivation may take up to 24 hours."
                                                                    aria-describedby="tooltip636824"></i>
                                                            </p>
                                                            <div class="form-check form-switch mb-2">
                                                                <button type="button" onclick="deactivateAcc();"
                                                                    class="btn btn-danger btn-wave waves-effect waves-light">{{ __('Deactivate Account') }}</button>

                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="platformManage" role="tabpanel">
                                    <div class="accordion accordion-primary my-3" id="cngLang">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingPrimaryOne">
                                                <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#langSetting" aria-expanded="false"
                                                    aria-controls="langSetting">
                                                    {{ __('Language Settings') }}
                                                </button>
                                            </h2>
                                            <div id="langSetting" class="accordion-collapse collapse show"
                                                aria-labelledby="headingPrimaryOne" data-bs-parent="#cngLang">
                                                <div class="accordion-body">
                                                    <div>
                                                        <div class="input-group mb-3">
                                                            <label class="input-group-text"
                                                                for="defaultLnag">{{ __('Default Language (Phishing Emails)') }}</label>
                                                            <select class="form-select" id="default_phish_lang">
                                                                <option value="sq">Albanian</option>
                                                                <option value="ar">Arabic</option>
                                                                <option value="az">Azerbaijani</option>
                                                                <option value="bn">Bengali</option>
                                                                <option value="bg">Bulgarian</option>
                                                                <option value="ca">Catalan</option>
                                                                <option value="zh">Chinese</option>
                                                                <option value="zt">Chinese (traditional)</option>
                                                                <option value="cs">Czech</option>
                                                                <option value="da">Danish</option>
                                                                <option value="nl">Dutch</option>
                                                                <option value="en" selected="">English</option>
                                                                <option value="eo">Esperanto</option>
                                                                <option value="et">Estonian</option>
                                                                <option value="fi">Finnish</option>
                                                                <option value="fr">French</option>
                                                                <option value="de">German</option>
                                                                <option value="el">Greek</option>
                                                                <option value="he">Hebrew</option>
                                                                <option value="hi">Hindi</option>
                                                                <option value="hu">Hungarian</option>
                                                                <option value="id">Indonesian</option>
                                                                <option value="ga">Irish</option>
                                                                <option value="it">Italian</option>
                                                                <option value="ja">Japanese</option>
                                                                <option value="ko">Korean</option>
                                                                <option value="lv">Latvian</option>
                                                                <option value="lt">Lithuanian</option>
                                                                <option value="ms">Malay</option>
                                                                <option value="nb">Norwegian</option>
                                                                <option value="fa">Persian</option>
                                                                <option value="pl">Polish</option>
                                                                <option value="pt">Portuguese</option>
                                                                <option value="ro">Romanian</option>
                                                                <option value="ru">Russian</option>
                                                                <option value="sk">Slovak</option>
                                                                <option value="sl">Slovenian</option>
                                                                <option value="es">Spanish</option>
                                                                <option value="sv">Swedish</option>
                                                                <option value="tl">Tagalog</option>
                                                                <option value="th">Thai</option>
                                                                <option value="tr">Turkish</option>
                                                                <option value="uk">Ukranian</option>
                                                                <option value="ur">Urdu</option>
                                                            </select>
                                                        </div>
                                                        <div class="input-group mb-3">
                                                            <label class="input-group-text"
                                                                for="default_train_lang">{{ __('Default Language (Training Modules)') }}</label>
                                                            <select class="form-select" id="default_train_lang">
                                                                <option value="dynamic">Dynamic Translation</option>
                                                                <option value="sq">Albanian</option>
                                                                <option value="ar">Arabic</option>
                                                                <option value="az">Azerbaijani</option>
                                                                <option value="bn">Bengali</option>
                                                                <option value="bg">Bulgarian</option>
                                                                <option value="ca">Catalan</option>
                                                                <option value="zh">Chinese</option>
                                                                <option value="zt">Chinese (traditional)</option>
                                                                <option value="cs">Czech</option>
                                                                <option value="da">Danish</option>
                                                                <option value="nl">Dutch</option>
                                                                <option value="en" selected="">English</option>
                                                                <option value="eo">Esperanto</option>
                                                                <option value="et">Estonian</option>
                                                                <option value="fi">Finnish</option>
                                                                <option value="fr">French</option>
                                                                <option value="de">German</option>
                                                                <option value="el">Greek</option>
                                                                <option value="he">Hebrew</option>
                                                                <option value="hi">Hindi</option>
                                                                <option value="hu">Hungarian</option>
                                                                <option value="id">Indonesian</option>
                                                                <option value="ga">Irish</option>
                                                                <option value="it">Italian</option>
                                                                <option value="ja">Japanese</option>
                                                                <option value="ko">Korean</option>
                                                                <option value="lv">Latvian</option>
                                                                <option value="lt">Lithuanian</option>
                                                                <option value="ms">Malay</option>
                                                                <option value="nb">Norwegian</option>
                                                                <option value="fa">Persian</option>
                                                                <option value="pl">Polish</option>
                                                                <option value="pt">Portuguese</option>
                                                                <option value="ro">Romanian</option>
                                                                <option value="ru">Russian</option>
                                                                <option value="sk">Slovak</option>
                                                                <option value="sl">Slovenian</option>
                                                                <option value="es">Spanish</option>
                                                                <option value="sv">Swedish</option>
                                                                <option value="tl">Tagalog</option>
                                                                <option value="th">Thai</option>
                                                                <option value="tr">Turkish</option>
                                                                <option value="uk">Ukranian</option>
                                                                <option value="ur">Urdu</option>
                                                            </select>
                                                        </div>
                                                        <div class="input-group mb-3">
                                                            <label class="input-group-text"
                                                                for="default_notifi_lang">{{ __('Default Language (Notifications)') }}</label>
                                                            <select class="form-select" id="default_notifi_lang">
                                                                <option value="en">English</option>
                                                                <option value="sq">Albanian</option>
                                                                <option value="ar">Arabic</option>
                                                                <option value="az">Azerbaijani</option>
                                                                <option value="bn">Bengali</option>
                                                                <option value="bg">Bulgarian</option>
                                                                <option value="ca">Catalan</option>
                                                                <option value="zh">Chinese</option>
                                                                <option value="zt">Chinese (traditional)</option>
                                                                <option value="cs">Czech</option>
                                                                <option value="da">Danish</option>
                                                                <option value="nl">Dutch</option>
                                                                <option value="en" selected="">English</option>
                                                                <option value="eo">Esperanto</option>
                                                                <option value="et">Estonian</option>
                                                                <option value="fi">Finnish</option>
                                                                <option value="fr">French</option>
                                                                <option value="de">German</option>
                                                                <option value="el">Greek</option>
                                                                <option value="he">Hebrew</option>
                                                                <option value="hi">Hindi</option>
                                                                <option value="hu">Hungarian</option>
                                                                <option value="id">Indonesian</option>
                                                                <option value="ga">Irish</option>
                                                                <option value="it">Italian</option>
                                                                <option value="ja">Japanese</option>
                                                                <option value="ko">Korean</option>
                                                                <option value="lv">Latvian</option>
                                                                <option value="lt">Lithuanian</option>
                                                                <option value="ms">Malay</option>
                                                                <option value="nb">Norwegian</option>
                                                                <option value="fa">Persian</option>
                                                                <option value="pl">Polish</option>
                                                                <option value="pt">Portuguese</option>
                                                                <option value="ro">Romanian</option>
                                                                <option value="ru">Russian</option>
                                                                <option value="sk">Slovak</option>
                                                                <option value="sl">Slovenian</option>
                                                                <option value="es">Spanish</option>
                                                                <option value="sv">Swedish</option>
                                                                <option value="tl">Tagalog</option>
                                                                <option value="th">Thai</option>
                                                                <option value="tr">Turkish</option>
                                                                <option value="uk">Ukranian</option>
                                                                <option value="ur">Urdu</option>
                                                            </select>
                                                        </div>
                                                        <div class="card-footer p-2 text-end">
                                                            <button class="btn btn-primary m-1" id="updateLang">
                                                                {{ __('Update') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion accordion-primary my-3" id="phishEdu">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingPhishEdu">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#phish_edu"
                                                    aria-expanded="false" aria-controls="phish_edu">
                                                    {{ __('Phish Education') }}
                                                </button>
                                            </h2>
                                            <div id="phish_edu" class="accordion-collapse collapse"
                                                aria-labelledby="headingPhishEdu" data-bs-parent="#phishEdu">
                                                <div class="accordion-body">
                                                    <div>
                                                        <h6>{{ __('If an employee falls victim to a phishing website, what action should be taken?') }}
                                                        </h6>
                                                        <span>{{ __('Note: This setting configures the default selection for new campaigns. This setting can be modified on a campaign-by-campaign basis.') }}</span>
                                                        <div class="my-3">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="phish_redirect" value="simuEducation"
                                                                    id="simuEducation">
                                                                <label class="form-check-label" for="simuEducation">
                                                                    {{ __('Redirect to the') }} {{ $companyName }}
                                                                    {{ __('education website') }}
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="phish_redirect" value="byoEducation"
                                                                    id="byoEducation">
                                                                <label class="form-check-label" for="byoEducation">
                                                                    {{ __('Redirect to my own education website') }}
                                                                </label>
                                                            </div>
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="phish_redirect" value="noEducation"
                                                                    id="noEducation">
                                                                <label class="form-check-label" for="noEducation">
                                                                    {{ __("Don't do anything") }}
                                                                </label>
                                                            </div>

                                                        </div>
                                                        <div id="redirectUrl">
                                                            <input type="text" class="form-control" id="redirect_url"
                                                                placeholder="https://yourwebsite.com">
                                                        </div>

                                                        <div class="card-footer p-2 text-end">
                                                            <button class="btn btn-primary m-1" id="updatePhishEdu">
                                                                {{ __('Update') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion accordion-primary my-3" id="phishReporting">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingreportPhish">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#report_phish"
                                                    aria-expanded="false" aria-controls="report_phish">
                                                    {{ __('Phish Reporting') }}
                                                </button>
                                            </h2>
                                            <div id="report_phish" class="accordion-collapse collapse"
                                                aria-labelledby="headingreportPhish" data-bs-parent="#phishReporting">
                                                <div class="accordion-body">
                                                    <div class="my-3">
                                                        <div class="d-flex justify-content-between">

                                                            <h6>{{ __('Enable employees to report phishing via Gmail, Office 365 and Outlook?') }}
                                                            </h6>
                                                            <div
                                                                class="custom-toggle-switch d-flex align-items-center mb-4">
                                                                <input id="reportngVia" name="reportVia"
                                                                    onchange="reportingChange(this)" type="checkbox"
                                                                    @if ($all_settings->company_settings->phish_reporting == 1) checked @endif>
                                                                <label for="reportngVia" class="label-success"></label>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion accordion-primary my-3" id="trainingReminder">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingTrainingRemind">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#train_remind"
                                                    aria-expanded="false" aria-controls="train_remind">
                                                    {{ __('Notification Settings') }}
                                                </button>
                                            </h2>
                                            <div id="train_remind" class="accordion-collapse collapse"
                                                aria-labelledby="headingTrainingRemind"
                                                data-bs-parent="#trainingReminder">
                                                <div class="accordion-body">
                                                    <div class="my-3 d-flex justify-content-between align-items-center">
                                                        <div>

                                                            <h6>{{ __('Deliver Training Assignment Reminders?') }}</h6>
                                                        </div>
                                                        <div class="d-flex">
                                                            <label for="freqdays"
                                                                class="mx-3">{{ __('Frequency Days') }}</label>
                                                            <input type="number" class="form-control" id="freqdays"
                                                                style="width:70px;" min="0" max="30">
                                                            <button type="button"
                                                                class="btn mx-2 btn-primary btn-wave waves-effect waves-light"
                                                                id="trainFreq">{{ __('Save') }}</button>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane p-0" id="siem" role="tabpanel">
                                    <div class="p-sm-3 p-0">
                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="provider" class="form-label">{{ __('Provider') }}</label>
                                                <select id="provider" class="form-control form-control-line">
                                                    <option value="splunk"
                                                        @if ($all_settings->siemSettings?->provider_name == 'splunk') selected @endif>
                                                        {{ __('Splunk') }}
                                                    </option>
                                                    <option value="webhook"
                                                        @if ($all_settings->siemSettings?->provider_name == 'webhook') selected @endif>
                                                        {{ __('Webhook') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row gy-4 mb-4">

                                            <div class="col-xl-12">
                                                <label for="provider" class="form-label">{{ __('URL') }}<sup
                                                        class="text-danger">*</sup></label>
                                                <input type="text" value="{{ $all_settings->siemSettings?->url }}"
                                                    class="form-control" id="provider_url"
                                                    placeholder="https://log.provider.com">
                                            </div>

                                            <div class="col-xl-12" id="tokenField">
                                                <label for="authToken"
                                                    class="form-label">{{ __('Authorization Token') }}</label>
                                                <input type="password"
                                                    value="{{ $all_settings->siemSettings?->token ?? '' }}"
                                                    class="form-control" id="auth_token"
                                                    placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                                            </div>

                                        </div>
                                        <div class="d-flex gap-4">
                                            <label class="form-label mb-3">{{ __('Enabled') }}</label>
                                            <div class="custom-toggle-switch d-flex align-items-center mb-4">
                                                <input id="seimStatus" name="seimStatus" type="checkbox"
                                                    @if ($all_settings->siemSettings?->status == 1) checked @endif>
                                                <label for="seimStatus" class="label-success"></label>
                                            </div>
                                        </div>
                                        <div class="card-footer mb-3" style="margin-bottom: 53px !important;">
                                            <div class="float-end">
                                                <button class="btn btn-primary m-1" id="saveSiem">
                                                    {{ __('Save') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="tab-pane p-0" id="whiteLabel" role="tabpanel">
                                    @if ($whiteLabel)
                                        @if ($whiteLabel->approved_by_partner == 1)
                                            <p class="text-primary m-4 fw-3">Your White Label Request is approved by
                                                partner</p>
                                        @else
                                            <p class="text-primary m-4 fw-3">Your White Label Request is submitted</p>
                                        @endif
                                    @else
                                        <div class="card custom-card">
                                            <div class="card-body">
                                                <ul class="nav nav-tabs justify-content-center mb-5 tab-style-3"
                                                    id="myTab2" role="tablist">
                                                    <li class="nav-item me-0" role="presentation">
                                                        <button class="nav-link active home py-1" id="company-tab"
                                                            data-bs-toggle="tab" data-bs-target="#company-tab-pane"
                                                            type="button" role="tab"
                                                            aria-controls="company-tab-pane"
                                                            aria-selected="true">Company</button>
                                                    </li>
                                                    <li class="nav-item me-0" role="presentation">
                                                        <button class="nav-link about py-1" id="logo-tab"
                                                            data-bs-toggle="tab" data-bs-target="#logo-tab-pane"
                                                            type="button" role="tab" aria-controls="logo-tab-pane"
                                                            aria-selected="false">Logo</button>
                                                    </li>
                                                    <li class="nav-item me-0" role="presentation">
                                                        <button class="nav-link services py-1" id="smtp-tab"
                                                            data-bs-toggle="tab" data-bs-target="#smtp-tab-pane"
                                                            type="button" role="tab" aria-controls="smtp-tab-pane"
                                                            aria-selected="false">SMTP</button>
                                                    </li>
                                                </ul>

                                                <form action="{{ route('save.white.label') }}" method="post"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="tab-content" id="myTabContent1">
                                                        <div class="tab-pane fade show active text-muted"
                                                            id="company-tab-pane" role="tabpanel"
                                                            aria-labelledby="company-tab" tabindex="0">

                                                            <div class="row gy-4 mb-4">
                                                                <div class="col-xl-12">
                                                                    <label for="company_name"
                                                                        class="form-label">{{ __('Company Name') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="company_name" id="company_name"
                                                                        placeholder="SimUphish">
                                                                </div>
                                                                <div class="col-xl-12">
                                                                    <label for="company_email"
                                                                        class="form-label">{{ __('Company Email') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="mail" class="form-control"
                                                                        name="company_email" id="company_email"
                                                                        placeholder="xyz@gmail.com">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="domain"
                                                                        class="form-label">{{ __('Domain') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="domain" id="domain"
                                                                        placeholder="xyz.com">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="learn_domain"
                                                                        class="form-label">{{ __('Learn Domain') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="learn_domain" id="learn_domain"
                                                                        placeholder="learn.simpuphish.com">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <p class="fs-15 mb-2 fw-semibold text-dark">Add these
                                                                        records to point your domain to our platform</p>
                                                                    <div class="table-responsive">
                                                                        <table class="table text-nowrap">
                                                                            <thead class="table-primary">
                                                                                <tr>
                                                                                    <th scope="col">Type</th>
                                                                                    <th scope="col">Name</th>
                                                                                    <th scope="col">Content</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <tr>
                                                                                    <th scope="row">
                                                                                        A
                                                                                    </th>
                                                                                    <td class="app_domain">
                                                                                        --
                                                                                    </td>
                                                                                    <td>
                                                                                        95.216.212.166
                                                                                    </td>
                                                                                </tr>
                                                                                <tr>
                                                                                    <th scope="row">
                                                                                        A
                                                                                    </th>
                                                                                    <td class="learn_domain">
                                                                                        --
                                                                                    </td>
                                                                                    <td>
                                                                                        95.216.212.166
                                                                                    </td>
                                                                                </tr>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>



                                                                <div class="card-footer">
                                                                    <div class="float-end">

                                                                        <button type="button"
                                                                            class="btn btn-primary mt-2" id="prevLogoTab">
                                                                            Next
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        </div>

                                                        <div class="tab-pane fade text-muted" id="logo-tab-pane"
                                                            role="tabpanel" aria-labelledby="logo-tab" tabindex="0">

                                                            <div class="row gy-4 mb-4">

                                                                <div class="col-xl-12">
                                                                    <label for="dark_logo"
                                                                        class="form-label">{{ __('Dark Logo') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="file" class="form-control"
                                                                        name="dark_logo" id="dark_logo">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="light_logo"
                                                                        class="form-label">{{ __('Light Logo') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="file" class="form-control"
                                                                        name="light_logo" id="light_logo">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="favicon"
                                                                        class="form-label">{{ __('Favicon') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="file" class="form-control"
                                                                        name="favicon" id="favicon">
                                                                </div>
                                                                <div class="card-footer">
                                                                    <div class="float-end">
                                                                        <button type="button"
                                                                            class="btn btn-primary mt-2"
                                                                            id="toCompanyTab">
                                                                            {{ __('Previous') }}
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-primary mt-2" id="toSmtpTab">
                                                                            {{ __('Next') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="tab-pane fade text-muted" id="smtp-tab-pane"
                                                            role="tabpanel" aria-labelledby="smtp-tab" tabindex="0">

                                                            <div class="row gy-4 mb-4">

                                                                <div class="col-xl-12">
                                                                    <label for="smtp_host"
                                                                        class="form-label">{{ __('SMTP Host') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        id="smtp_host" name="smtp_host"
                                                                        placeholder="">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="smtp_port"
                                                                        class="form-label">{{ __('SMTP Port') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="smtp_port" name="smtp_port"
                                                                        id="smtp_port" placeholder="">
                                                                </div>

                                                                <div class="col-xl-12">
                                                                    <label for="smtp_username"
                                                                        class="form-label">{{ __('SMTP Username') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="smtp_username" id="smtp_username"
                                                                        placeholder="">
                                                                </div>
                                                                <div class="col-xl-12">
                                                                    <label for="smtp_password"
                                                                        class="form-label">{{ __('SMTP Password') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="password" class="form-control"
                                                                        name="smtp_password" id="smtp_password"
                                                                        placeholder="">
                                                                </div>
                                                                <div class="col-xl-12">
                                                                    <label for="smtp_encryption"
                                                                        class="form-label">{{ __('SMTP Encryption') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="smtp_encryption" id="smtp_encryption"
                                                                        placeholder="">
                                                                </div>
                                                                <div class="col-xl-12">
                                                                    <label for="from_address"
                                                                        class="form-label">{{ __('From Address') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="from_address" id="from_address"
                                                                        placeholder="">
                                                                </div>
                                                                <div class="col-xl-12">
                                                                    <label for="from_name"
                                                                        class="form-label">{{ __('From Name') }}<sup
                                                                            class="text-danger">*</sup></label>
                                                                    <input type="text" class="form-control"
                                                                        name="from_name" id="from_name"
                                                                        placeholder="">
                                                                </div>
                                                                <div class="card-footer">
                                                                    <div class="float-end">
                                                                        <button type="button"
                                                                            class="btn btn-primary mt-2" id="toLogoTab">
                                                                            {{ __('Previous') }}
                                                                        </button>
                                                                        <button class="btn btn-primary mt-2"
                                                                            id="saveWhiteLabel">
                                                                            {{ __('Save') }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                        </div>



                                                    </div>


                                                </form>
                                            </div>
                                        </div>
                                    @endif

                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!--End::row-1 -->

        </div>
    </div>

    {{-- -------------------Modals------------------------ --}}

    <div class="modal fade" id="mfaModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">{{ __('Setup Multi Factor Authentication') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div>
                        <div class="my-5 d-flex justify-content-center" style="margin-bottom: 1rem !important;">
                            <img src="" alt="mfa_qr" id="mfa_qr" width="200">

                        </div>
                        <div class="card custom-card">
                            <div class="card-body p-5">
                                <p class="h5 fw-semibold mb-2 text-center">{{ __('Enter MFA Code') }}</p>
                                <p class="text-center">{{ __('Enter the code from Google Authenticator') }}</p>
                                <form action="{{ route('settings.verify.mfa') }}" method="post">
                                    @csrf
                                    <div class="row gy-3">
                                        <div class="col-xl-12">
                                            <input type="text" class="form-control form-control-lg"
                                                name="totp_code" placeholder="xxxxxx">
                                            <input type="hidden" name="secret" value="" id="mfa_secret">
                                        </div>
                                        <div class="col-xl-12 d-grid mt-2">
                                            <button type="submit" name="verifyMfaCode"
                                                class="btn btn-lg btn-primary">{{ __('Verify') }}</button>
                                        </div>
                                    </div>
                                </form>


                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    {{-- -------------------Modals------------------------ --}}


    {{-- ------------------------------Toasts---------------------- --}}

    <x-toast />



    @push('newcss')
        <style>
            .platform-info {
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
        </style>
    @endpush

    @push('newscripts')
        <script>
            var country = "{{ $all_settings->company_settings->country }}";
            var timeZone = "{{ $all_settings->company_settings->time_zone }}";
            var dateFormat = "{{ $all_settings->company_settings->date_format }}";
            var default_phishing_email_lang = "{{ $all_settings->company_settings->default_phishing_email_lang }}";
            var default_training_lang = "{{ $all_settings->company_settings->default_training_lang }}";
            var default_notifications_lang = "{{ $all_settings->company_settings->default_notifications_lang }}";
            var phish_redirect = "{{ $all_settings->company_settings->phish_redirect }}";
            var phish_redirect_url = "{{ $all_settings->company_settings->phish_redirect_url }}";
            var reportVia = "{{ $all_settings->company_settings->phish_reporting }}";
            var deliverTrainingReminder = "{{ $all_settings->company_settings->training_assign_remind_freq_days }}";

            countryInput.value = country;
            timeZoneInput.value = timeZone;
            dateFormatInput.value = dateFormat;
            default_phish_lang.value = default_phishing_email_lang;
            default_train_lang.value = default_training_lang;
            default_notifi_lang.value = default_notifications_lang;
            freqdays.value = deliverTrainingReminder;

            $(`input[name="phish_redirect"][value="${phish_redirect}"]`).prop('checked', true);

            if (phish_redirect == 'byoEducation') {
                $("#redirectUrl").show();
                $("#redirectUrl input").val(phish_redirect_url);
            } else {
                $("#redirectUrl").hide();
                $("#redirectUrl input").val('');
            }


            document.getElementById('toLogoTab').addEventListener('click', function() {
                const logoTab = new bootstrap.Tab(document.querySelector('#logo-tab'));
                logoTab.show();
            });

            document.getElementById('toCompanyTab').addEventListener('click', function() {
                const companyTab = new bootstrap.Tab(document.querySelector('#company-tab'));
                companyTab.show();
            });

            document.getElementById('toSmtpTab').addEventListener('click', function() {
                const smtpTab = new bootstrap.Tab(document.querySelector('#smtp-tab'));
                smtpTab.show();
            });

            document.getElementById('prevLogoTab').addEventListener('click', function() {
                const logoTab = new bootstrap.Tab(document.querySelector('#logo-tab'));
                logoTab.show();
            });

            $("#updateProfileBtn").click(function(e) {
                var clickedBtn = $(this);
                clickedBtn.text("{{ __('Please wait...') }}");
                $.post({
                    url: '/settings/update-profile',
                    data: {
                        'updateProfile': 1,
                        'country': countryInput.value,
                        'timeZone': timeZoneInput.value,
                        'dateFormat': dateFormatInput.value
                    },
                    success: function(res) {
                        // var resJson = JSON.parse(res);
                        // alert(resJson.msg);
                        if (res.status == 1) {
                            clickedBtn.text("{{ __('Update Profile') }}");
                            Swal.fire({
                                title: res.msg,
                                icon: 'success',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        } else {
                            clickedBtn.text("{{ __('Update Profile') }}");
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }

                    }
                })
            })

            $("#updatePassBtn").click(function(e) {
                var clickedBtn = $(this);
                clickedBtn.text("{{ __('Please wait...') }}");
                $.post({
                    url: '/settings/update-password',
                    data: {
                        'updatePassword': 1,
                        'currentPassword': currentPassword.value,
                        'newPassword': newPassword.value,
                        'newPassword_confirmation': confirmPassword.value
                    },
                    success: function(res) {
                        if (res.status == 1) {
                            clickedBtn.text("{{ __('Update Password') }}");
                            Swal.fire({
                                title: res.msg,
                                icon: 'success',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        } else {
                            clickedBtn.text("{{ __('Update Password') }}");
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }
                    }
                })
            })

            $(`input[name="phish_redirect"]`).change(function(e) {
                var type = $(this).val();

                if (type == 'byoEducation') {
                    $("#redirectUrl").show();
                } else {
                    $("#redirectUrl").hide();
                    $("#redirectUrl input").val('');
                }

            });


            $('#domain').change(function() {
                let appDomain = $(this).val();
                $('.app_domain').text(appDomain);
            });

             $('#learn_domain').change(function() {
                let learnDomain = $(this).val();
                $('.learn_domain').text(learnDomain);
            });

            function mfaChange(e) {
                if ($(e).is(":checked")) {

                    // console.log("checked");
                    $.post({
                        url: '/settings/update-mfa',
                        data: {
                            'updateMFA': 1,
                            'status': 1
                        },
                        success: function(res) {
                            if (res.status == 1) {

                                $("#mfa_qr").attr("src", res.QR_Image);
                                $("#mfa_secret").val(res.secretKey);

                                $("#mfaModal").modal('show');
                                // Swal.fire(
                                //     res.msg,
                                //     '',
                                //     'success'
                                // )
                            } else {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                            // console.log(res)
                        }
                    })
                } else {
                    // console.log("not checked");
                    $.post({
                        url: '/settings/update-mfa',
                        data: {
                            'updateMFA': 1,
                            'status': 0
                        },
                        success: function(res) {
                            if (res.status == 1) {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                        }
                    })
                }
            }


            $("#updateLang").click(function(e) {
                var clickedBtn = $(this);
                clickedBtn.text("{{ __('Please wait...') }}");
                $.post({
                    url: '/settings/update-lang',
                    data: {
                        'updateLang': 1,
                        'default_phish_lang': default_phish_lang.value,
                        'default_train_lang': default_train_lang.value,
                        'default_notifi_lang': default_notifi_lang.value
                    },
                    success: function(res) {
                        if (res.status == 1) {
                            clickedBtn.text("Update");
                            Swal.fire({
                                title: res.msg,
                                icon: 'success',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        } else {
                            clickedBtn.text("Update");
                            Swal.fire({
                                title: res.msg,
                                icon: 'error',
                                confirmButtonText: "{{ __('OK') }}"
                            })
                        }
                    }
                })
            })

            $("#updatePhishEdu").click(function(e) {
                var clickedBtn = $(this);
                var redirectType = $(`input[name="phish_redirect"]:checked`).val();

                if (redirectType == 'byoEducation' && redirect_url.value == '') {
                    // alert("Please enter the website url");
                    Swal.fire({
                        title: "{{ __('Please enter the website url') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                } else {

                    clickedBtn.text("{{ __('Please wait...') }}");
                    $.post({
                        url: '/settings/update-phish-edu',
                        data: {
                            'updatePhishingEdu': 1,
                            'redirect_url': redirect_url.value,
                            'redirect_type': redirectType
                        },
                        success: function(res) {
                            // var resJson = JSON.parse(res);
                            // alert(resJson.msg);

                            if (res.status == 1) {
                                clickedBtn.text("Update");
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                clickedBtn.text("Update");
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                        }
                    })
                }
                // console.log(redirectType);
            })

            $('#freqdays').on('input', function() {
                var inputValue = $(this).val();
                // Check if the input is a number and if it's greater than 30
                if ($.isNumeric(inputValue) && parseInt(inputValue) > 30) {
                    // If the input value is greater than 30, reset the input value to 30
                    $(this).val(30);
                    alert("Number greater than 30 is not allowed. Input set to 30.");
                }
            });

            $("#trainFreq").click(function(e) {
                var clickedBtn = $(this);
                if ($.isNumeric(freqdays.value)) {

                    clickedBtn.text("{{ __('Please wait...') }}");
                    $.post({
                        url: '/settings/update-train-freq',
                        data: {
                            'updateTrainFreq': 1,
                            'days': freqdays.value
                        },
                        success: function(res) {
                            // var resJson = JSON.parse(res);
                            // alert(resJson.msg);

                            if (res.status == 1) {
                                clickedBtn.text("save");
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                clickedBtn.text("save");
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                            // window.location.href = window.location.href;
                        }
                    })
                } else {
                    // alert("");
                    Swal.fire({
                        title: "{{ __('Entered value is not a numeric value') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                }
            })



            function reportingChange(e) {
                if ($(e).is(":checked")) {

                    // console.log("checked");
                    $.post({
                        url: '/settings/update-reporting',
                        data: {
                            'updateReporting': 1,
                            'status': 1
                        },
                        success: function(res) {
                            if (res.status == 1) {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                        }
                    })
                } else {
                    // console.log("not checked");
                    $.post({
                        url: '/settings/update-reporting',
                        data: {
                            'updateReporting': 1,
                            'status': 0
                        },
                        success: function(res) {
                            if (res.status == 1) {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                        }
                    })
                }
            }

            function deactivateAcc() {
                Swal.fire({
                    title: "{{ __('Are you sure?') }}",
                    text: "{{ __('After deactivation your services will be on hold. You have to contact to your service provider to reactivate your account') }}",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e6533c',
                    cancelButtonColor: '#d33',
                    confirmButtonText: "{{ __('Deactivate') }}",
                    cancelButtonText: "{{ __('Cancel') }}"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: '/settings/acc-dectivate',
                            data: {
                                'accDeactivate': 1
                            },
                            success: function(res) {
                                // var resJson = JSON.parse(res);
                                // alert(resJson.msg);
                                window.location.href = "{{ route('logout') }}";
                            }
                        })
                    }
                })

            }
        </script>

        <script>
            // $(document).ready(function() {
            //     $('#provider').change(function() {
            //         var selectedValue = $(this).val();
            //         if (selectedValue == 'webhook') {
            //             $('#tokenField input').val('');
            //             $('#tokenField').hide();
            //         } else {
            //             $('#tokenField input').val('');
            //             $('#tokenField').show();
            //         }
            //     });
            // });

            $('#provider_url').on('input', function() {
                var inputValue = $(this).val();
                if (inputValue.endsWith('/')) {
                    $(this).addClass('is-invalid').removeClass('is-valid');
                } else {
                    $(this).addClass('is-valid').removeClass('is-invalid');
                }
            });

            $('#saveSiem').click(function(e) {
                e.preventDefault();
                var provider = $('#provider').val();
                var provider_url = $('#provider_url').val();
                var auth_token = $('#auth_token').val();
                var status = $('#seimStatus').is(':checked') ? 1 : 0;
                if ((provider == 'webhook' || provider == 'splunk') && provider_url.endsWith('/')) {
                    Swal.fire({
                        title: "{{ __('Please enter a valid URL') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                } else if (provider == 'splunk' && auth_token == '') {
                    Swal.fire({
                        title: "{{ __('Please enter the authorization token') }}",
                        icon: 'error',
                        confirmButtonText: "{{ __('OK') }}"
                    })
                } else {
                    $.post({
                        url: '/settings/update-siem',
                        data: {
                            'provider': provider,
                            'provider_url': provider_url,
                            'auth_token': auth_token,
                            'status': status
                        },
                        success: function(res) {
                            if (res.status == 1) {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'success',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            } else {
                                Swal.fire({
                                    title: res.msg,
                                    icon: 'error',
                                    confirmButtonText: "{{ __('OK') }}"
                                })
                            }
                        }
                    })
                }
            });
        </script>
    @endpush

@endsection
