/*
 * فایل اسکریپت اصلی
 * شامل افکت‌های تایپ و انیمیشن‌های اسکرول (Fade-in).
 */

document.addEventListener("DOMContentLoaded", () => {

    // --- داده‌های محتوای تایپ‌شونده ---

    /* !! نقطه سفارشی‌سازی: متن «درباره مدرس»
      محتوای این آرایه خط به خط در بخش «درباره» تایپ می‌شود.
    */
    const aboutLines = [
        "من ریحانه زراعتکار هستم، دارنده مدرک عالیه از کانون زبان ایران و رتبهٔ ۱ کنکور سراسری در درس عربی.",
        "بیش از هشت سال تجربهٔ تدریس در مدارس نمونهٔ دولتی و مؤسسات کنکوری دارم و به‌عنوان مدرس عربی دورهٔ متوسطهٔ اول و دوم فعالیت کرده‌ام.",
        "همچنین مدرس حل تمرین صرف و نحو در دانشگاه فردوسی مشهد بوده‌ام و در کارگاه‌های کنکوری برای تمامی رشته‌ها تدریس کرده‌ام.",
        "هدفم ایجاد مسیر آموزشی هدفمند برای دانش‌آموزان است تا با انرژی و تجربه‌ام همراهشان باشم و به کسب نمرهٔ ۲۰ و درصدهای بالا در کنکور کمک کنم."
    ];

    /* !! نقطه سفارشی‌سازی: موارد «سوابق»
      محتوای این آرایه آیتم به آیتم در لیست «سوابق» تایپ می‌شود.
    */
    const resumeItems = [
        "کارشناسی زبان و ادبیات عرب",
        "حائز مدرک عالیه کانون زبان ایران",
        "معلم عربی دوره متوسطه اول و دوم",
        "کسب رتبهٔ ۱ در درس عربی کنکور سراسری",
        "مدرس حل تمرین صرف و نحو دانشگاه فردوسی مشهد",
        "مدرس عربی کارگاه‌های کنکوری از کلیه رشته‌های تحصیلی",
        "هشت سال سابقهٔ تدریس توانمند در مدارس نمونه دولتی و مؤسسات کنکوری",
        "ایجاد مسیر آموزشی مناسب برای کسب نمرهٔ ۲۰ در امتحانات و درصد بالا در کنکور"
    ];

    // --- توابع تایپ‌کننده ---

    /**
     * متن را در یک پاراگراف (با <br>) تایپ می‌کند.
     * @param {string} elementId - ID المان (باید <p> باشد)
     * @param {string[]} lines - آرایه‌ای از رشته‌ها برای تایپ
     * @param {number} charDelay - تأخیر بین هر کاراکتر (ms)
     * @param {number} lineDelay - تأخیر بعد از اتمام هر خط (ms)
     */
    function typeParagraph(elementId, lines, charDelay = 50, lineDelay = 500) {
        const element = document.getElementById(elementId);
        if (!element) return;

        element.innerHTML = '<span class="cursor"></span>';
        const cursor = element.querySelector('.cursor');
        let lineIndex = 0;
        let charIndex = 0;

        function type() {
            if (lineIndex >= lines.length) {
                cursor.style.display = 'none'; // پنهان کردن نشانگر در پایان
                return;
            }

            const currentLine = lines[lineIndex];

            if (charIndex < currentLine.length) {
                // اضافه کردن کاراکتر قبل از نشانگر
                const textNode = document.createTextNode(currentLine[charIndex]);
                element.insertBefore(textNode, cursor);
                charIndex++;
                setTimeout(type, charDelay);
            } else {
                // رفتن به خط بعد
                element.insertBefore(document.createElement('br'), cursor);
                lineIndex++;
                charIndex = 0;
                setTimeout(type, lineDelay);
            }
        }
        type();
    }

    /**
     * موارد را در یک لیست (با <li>) تایپ می‌کند.
     * @param {string} elementId - ID المان (باید <ul> باشد)
     * @param {string[]} items - آرایه‌ای از رشته‌ها برای تایپ
     * @param {number} charDelay - تأخیر بین هر کاراکتر (ms)
     * @param {number} itemDelay - تأخیر بعد از اتمام هر آیتم (ms)
     */
    function typeList(elementId, items, charDelay = 40, itemDelay = 300) {
        const element = document.getElementById(elementId);
        if (!element) return;

        let itemIndex = 0;
        let charIndex = 0;
        let currentLi = null;

        function type() {
            if (itemIndex >= items.length) {
                return; // پایان کار
            }

            // ایجاد آیتم لیست جدید اگر وجود ندارد
            if (currentLi === null) {
                currentLi = document.createElement('li');
                element.appendChild(currentLi);
                currentLi.innerHTML = '<span class="cursor"></span>';
            }

            const currentItem = items[itemIndex];
            const cursor = currentLi.querySelector('.cursor');

            if (charIndex < currentItem.length) {
                // اضافه کردن کاراکتر قبل از نشانگر
                const textNode = document.createTextNode(currentItem[charIndex]);
                currentLi.insertBefore(textNode, cursor);
                charIndex++;
                setTimeout(type, charDelay);
            } else {
                // رفتن به آیتم بعد
                cursor.remove(); // حذف نشانگر از آیتم فعلی
                itemIndex++;
                charIndex = 0;
                currentLi = null;
                setTimeout(type, itemDelay);
            }
        }
        type();
    }


    // --- مشاهده‌گر انیمیشن‌ها (Intersection Observer) ---
    // این بخش انیمیشن‌های fade-in و شروع تایپ را مدیریت می‌کند.

    const sections = document.querySelectorAll('section');

    const observerOptions = {
        root: null, // مشاهده نسبت به viewport
        threshold: 0.1, // ۱۰٪ بخش دیده شود
    };

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;

            // ۱. فعال کردن انیمیشن محو شدن (Fade-in)
            entry.target.classList.add('visible');

            // ۲. فعال کردن انیمیشن تایپ (فقط یک بار)
            if (entry.target.id === 'about' && !entry.target.dataset.typed) {
                entry.target.dataset.typed = 'true'; // علامت‌گذاری برای جلوگیری از اجرای مجدد
                typeParagraph('typewriter-about', aboutLines, 30, 400);
            }

            if (entry.target.id === 'resume' && !entry.target.dataset.typed) {
                entry.target.dataset.typed = 'true'; // علامت‌گذاری
                typeList('typewriter-resume', resumeItems, 30, 200);
            }

            // ۳. توقف مشاهده این بخش پس از فعال‌سازی
            obs.unobserve(entry.target);
        });
    }, observerOptions);

    // شروع مشاهده همه بخش‌ها
    sections.forEach(section => {
        observer.observe(section);
    });

});