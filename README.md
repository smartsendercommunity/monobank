# monobank

Тестовый ключ можно получить на https://api.monobank.ua
Боевой ключ можно получить на https://fop.monobank.ua

В данной интеграции используется метод создания счетов, описанный тут: https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1create/post
и метод проверки факта оплаты (только после получения хука об оплате для валидации), описаный тут: https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1status?invoiceId={invoiceId}/get
