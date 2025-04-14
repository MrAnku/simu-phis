<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

  'accepted' => 'Поле :attribute должно быть принято.',
'accepted_if' => 'Поле :attribute должно быть принято, если :other равно :value.',
'active_url' => 'Поле :attribute должно быть допустимым URL.',
'after' => 'Поле :attribute должно быть датой после :date.',
'after_or_equal' => 'Поле :attribute должно быть датой после или равной :date.',
'alpha' => 'Поле :attribute должно содержать только буквы.',
'alpha_dash' => 'Поле :attribute должно содержать только буквы, цифры, дефисы и нижние подчёркивания.',
'alpha_num' => 'Поле :attribute должно содержать только буквы и цифры.',
'array' => 'Поле :attribute должно быть массивом.',
'ascii' => 'Поле :attribute должно содержать только однобайтовые буквенно-цифровые символы и знаки.',
'before' => 'Поле :attribute должно быть датой до :date.',
'before_or_equal' => 'Поле :attribute должно быть датой до или равной :date.',
'between' => [
    'array' => 'Поле :attribute должно содержать от :min до :max элементов.',
    'file' => 'Размер файла в поле :attribute должен быть от :min до :max килобайт.',
    'numeric' => 'Поле :attribute должно быть между :min и :max.',
    'string' => 'Поле :attribute должно содержать от :min до :max символов.',
],
'boolean' => 'Поле :attribute должно быть истинным или ложным.',

















    'can' => 'Поле :attribute содержит неразрешённое значение.',
'confirmed' => 'Подтверждение поля :attribute не совпадает.',
'contains' => 'Поле :attribute не содержит обязательное значение.',
'current_password' => 'Неверный пароль.',
'date' => 'Поле :attribute должно быть корректной датой.',
'date_equals' => 'Поле :attribute должно быть датой, равной :date.',
'date_format' => 'Поле :attribute должно соответствовать формату :format.',
'decimal' => 'Поле :attribute должно содержать :decimal знаков после запятой.',
'declined' => 'Поле :attribute должно быть отклонено.',
'declined_if' => 'Поле :attribute должно быть отклонено, если :other равно :value.',
'different' => 'Поля :attribute и :other должны различаться.',
'digits' => 'Поле :attribute должно содержать :digits цифр.',
'digits_between' => 'Поле :attribute должно содержать от :min до :max цифр.',
'dimensions' => 'Поле :attribute имеет недопустимые размеры изображения.',
'distinct' => 'Поле :attribute содержит повторяющееся значение.',
'doesnt_end_with' => 'Поле :attribute не должно заканчиваться одним из следующих значений: :values.',
'doesnt_start_with' => 'Поле :attribute не должно начинаться с одного из следующих значений: :values.',
'email' => 'Поле :attribute должно быть действительным адресом электронной почты.',
'ends_with' => 'Поле :attribute должно заканчиваться одним из следующих значений: :values.',
'enum' => 'Выбранное значение для :attribute недопустимо.',
'exists' => 'Выбранное значение для :attribute недопустимо.',
'extensions' => 'Поле :attribute должно иметь одно из следующих расширений: :values.',
'file' => 'Поле :attribute должно быть файлом.',
'filled' => 'Поле :attribute должно содержать значение.',
'gt' => [
    'array' => 'Поле :attribute должно содержать более :value элементов.',
    'file' => 'Размер файла в поле :attribute должен быть больше :value килобайт.',
    'numeric' => 'Поле :attribute должно быть больше :value.',
    'string' => 'Поле :attribute должно содержать более :value символов.',
],
'gte' => [
    'array' => 'Поле :attribute должно содержать :value элементов или больше.',
    'file' => 'Размер файла в поле :attribute должен быть не меньше :value килобайт.',
    'numeric' => 'Поле :attribute должно быть не меньше :value.',
    'string' => 'Поле :attribute должно содержать не менее :value символов.',
],
'hex_color' => 'Поле :attribute должно быть допустимым шестнадцатеричным цветом.',

















   'image' => 'Поле :attribute должно быть изображением.',
'in' => 'Выбранное значение для :attribute недопустимо.',
'in_array' => 'Поле :attribute должно существовать в :other.',
'integer' => 'Поле :attribute должно быть целым числом.',
'ip' => 'Поле :attribute должно быть действительным IP-адресом.',
'ipv4' => 'Поле :attribute должно быть действительным IPv4-адресом.',
'ipv6' => 'Поле :attribute должно быть действительным IPv6-адресом.',
'json' => 'Поле :attribute должно быть допустимой JSON-строкой.',
'list' => 'Поле :attribute должно быть списком.',
'lowercase' => 'Поле :attribute должно быть в нижнем регистре.',
'lt' => [
    'array' => 'Поле :attribute должно содержать менее :value элементов.',
    'file' => 'Поле :attribute должно быть меньше :value килобайт.',
    'numeric' => 'Поле :attribute должно быть меньше :value.',
    'string' => 'Поле :attribute должно содержать менее :value символов.',
],
'lte' => [
    'array' => 'Поле :attribute не должно содержать более :value элементов.',
    'file' => 'Поле :attribute должно быть не больше :value килобайт.',
    'numeric' => 'Поле :attribute должно быть не больше :value.',
    'string' => 'Поле :attribute должно содержать не более :value символов.',
],
'mac_address' => 'Поле :attribute должно быть допустимым MAC-адресом.',
'max' => [
    'array' => 'Поле :attribute не должно содержать более :max элементов.',
    'file' => 'Поле :attribute не должно превышать :max килобайт.',
    'numeric' => 'Поле :attribute не должно быть больше :max.',
    'string' => 'Поле :attribute не должно содержать более :max символов.',
],
'max_digits' => 'Поле :attribute не должно содержать более :max цифр.',
'mimes' => 'Поле :attribute должно быть файлом типа: :values.',
'mimetypes' => 'Поле :attribute должно быть файлом типа: :values.',
'min' => [
    'array' => 'Поле :attribute должно содержать не менее :min элементов.',
    'file' => 'Поле :attribute должно быть не менее :min килобайт.',
    'numeric' => 'Поле :attribute должно быть не меньше :min.',
    'string' => 'Поле :attribute должно содержать не менее :min символов.',
],
'min_digits' => 'Поле :attribute должно содержать не менее :min цифр.',
'missing' => 'Поле :attribute должно отсутствовать.',
'missing_if' => 'Поле :attribute должно отсутствовать, если :other равно :value.',
'missing_unless' => 'Поле :attribute должно отсутствовать, если только :other не равно :value.',
'missing_with' => 'Поле :attribute должно отсутствовать, если :values присутствует.',
'missing_with_all' => 'Поле :attribute должно отсутствовать, если :values присутствуют.',
'multiple_of' => 'Поле :attribute должно быть кратным :value.',
'not_in' => 'Выбранное значение для :attribute недопустимо.',
'not_regex' => 'Формат поля :attribute недопустим.',
'numeric' => 'Поле :attribute должно быть числом.',
'password' => [
    'letters' => 'Поле :attribute должно содержать хотя бы одну букву.',
    'mixed' => 'Поле :attribute должно содержать хотя бы одну заглавную и одну строчную букву.',
    'numbers' => 'Поле :attribute должно содержать хотя бы одну цифру.',
    'symbols' => 'Поле :attribute должно содержать хотя бы один символ.',
    'uncompromised' => 'Значение :attribute обнаружено в утечке данных. Пожалуйста, выберите другое значение.',
],
'present' => 'Поле :attribute должно присутствовать.',
'present_if' => 'Поле :attribute должно присутствовать, если :other равно :value.',
'present_unless' => 'Поле :attribute должно присутствовать, если только :other не равно :value.',
'present_with' => 'Поле :attribute должно присутствовать, если :values присутствует.',
'present_with_all' => 'Поле :attribute должно присутствовать, если :values присутствуют.',
'prohibited' => 'Поле :attribute запрещено.',
'prohibited_if' => 'Поле :attribute запрещено, если :other равно :value.',
'prohibited_if_accepted' => 'Поле :attribute запрещено, если :other принято.',
'prohibited_if_declined' => 'Поле :attribute запрещено, если :other отклонено.',
'prohibited_unless' => 'Поле :attribute запрещено, если только :other не содержится в :values.',
'prohibits' => 'Поле :attribute запрещает присутствие :other.',
'regex' => 'Формат поля :attribute недопустим.',
'required' => 'Поле :attribute обязательно для заполнения.',
'required_array_keys' => 'Поле :attribute должно содержать записи для: :values.',
'required_if' => 'Поле :attribute обязательно, если :other равно :value.',
'required_if_accepted' => 'Поле :attribute обязательно при принятии :other.',
'required_if_declined' => 'Поле :attribute обязательно при отклонении :other.',
'required_unless' => 'Поле :attribute обязательно, если :other не содержится в :values.',
'required_with' => 'Поле :attribute обязательно, если :values присутствует.',
'required_with_all' => 'Поле :attribute обязательно, если :values присутствуют.',
'required_without' => 'Поле :attribute обязательно, если :values отсутствует.',
'required_without_all' => 'Поле :attribute обязательно, если ни одно из :values не присутствует.',
'same' => 'Поле :attribute должно совпадать с :other.',
'size' => [
    'array' => 'Поле :attribute должно содержать :size элементов.',
    'file' => 'Поле :attribute должно быть размером :size килобайт.',
    'numeric' => 'Поле :attribute должно быть равным :size.',
    'string' => 'Поле :attribute должно содержать :size символов.',
],
'starts_with' => 'Поле :attribute должно начинаться с одного из следующих значений: :values.',
'string' => 'Поле :attribute должно быть строкой.',
'timezone' => 'Поле :attribute должно быть допустимым часовым поясом.',
'unique' => 'Значение :attribute уже используется.',
'uploaded' => 'Не удалось загрузить :attribute.',
'uppercase' => 'Поле :attribute должно быть в верхнем регистре.',
'url' => 'Поле :attribute должно быть допустимым URL.',
'ulid' => 'Поле :attribute должно быть допустимым ULID.',
'uuid' => 'Поле :attribute должно быть допустимым UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
