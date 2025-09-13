# سیستم مدیریت دوره‌های آموزشی

این پروژه یک سیستم مدیریت دوره‌های آموزشی است که امکان ایجاد، ویرایش، حذف و ثبت نام در دوره‌ها را فراهم می‌کند.

## ساختار پروژه

```
/project
  ├── api/
  │   ├── blog_api.php
  │   └── course_api.php
  ├── config/
  │   └── database.php
  ├── models/
  │   ├── BlogModel.php
  │   └── CourseModel.php
  ├── course_panel.html
  └── course_enrollment.html
```

## امکانات

### مدیریت دوره‌ها

- ایجاد دوره جدید
- ویرایش دوره‌های موجود
- حذف دوره‌ها
- انتشار و عدم انتشار دوره‌ها
- مدیریت جلسات دوره
- مشاهده آمار دوره‌ها

### ثبت نام در دوره‌ها

- مشاهده لیست دوره‌های موجود
- فیلتر کردن دوره‌ها بر اساس سطح و قیمت
- جستجو در دوره‌ها
- مشاهده جزئیات دوره و جلسات آن
- ثبت نام در دوره
- مشاهده دوره‌های ثبت نام شده

## نحوه استفاده

### پنل مدیریت دوره‌ها

برای دسترسی به پنل مدیریت دوره‌ها، فایل `course_panel.html` را در مرورگر باز کنید. در این پنل می‌توانید:

1. دوره‌های جدید ایجاد کنید
2. دوره‌های موجود را ویرایش کنید
3. جلسات دوره را مدیریت کنید
4. دوره‌ها را منتشر یا از انتشار خارج کنید
5. دوره‌ها را حذف کنید
6. آمار دوره‌ها را مشاهده کنید
7. ثبت نام‌های هر دوره را مشاهده کنید

### صفحه ثبت نام در دوره‌ها

برای دسترسی به صفحه ثبت نام در دوره‌ها، فایل `course_enrollment.html` را در مرورگر باز کنید. در این صفحه می‌توانید:

1. لیست دوره‌های موجود را مشاهده کنید
2. دوره‌ها را بر اساس سطح و قیمت فیلتر کنید
3. در دوره‌ها جستجو کنید
4. جزئیات هر دوره و جلسات آن را مشاهده کنید
5. در دوره‌ها ثبت نام کنید
6. دوره‌های ثبت نام شده خود را مشاهده کنید

## API

### API دوره‌ها

آدرس: `api/course_api.php`

#### متدهای GET

- `?action=list`: دریافت لیست دوره‌ها
- `?action=get&id={course_id}`: دریافت اطلاعات یک دوره خاص
- `?action=lessons&course_id={course_id}`: دریافت جلسات یک دوره
- `?action=enrollments&course_id={course_id}`: دریافت ثبت نام‌های یک دوره
- `?action=user_enrollments&user_id={user_id}`: دریافت دوره‌های ثبت نام شده یک کاربر
- `?action=search&keyword={keyword}`: جستجو در دوره‌ها
- `?action=stats`: دریافت آمار دوره‌ها

#### متدهای POST

- `action=create`: ایجاد دوره جدید
- `action=add_lesson`: افزودن جلسه جدید به دوره
- `action=enroll`: ثبت نام کاربر در دوره
- `action=publish`: انتشار دوره
- `action=unpublish`: عدم انتشار دوره

#### متدهای PUT

- `action=update`: به‌روزرسانی اطلاعات دوره
- `action=update_lesson`: به‌روزرسانی اطلاعات جلسه
- `action=cancel_enrollment`: لغو ثبت نام کاربر در دوره

#### متدهای DELETE

- `action=delete_course`: حذف دوره
- `action=delete_lesson`: حذف جلسه

## ساختار پایگاه داده

### جدول courses

```sql
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_fa VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    content TEXT,
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    price DECIMAL(10, 2) DEFAULT 0,
    duration VARCHAR(50),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

### جدول course_lessons

```sql
CREATE TABLE IF NOT EXISTS course_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title_fa VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    content TEXT,
    order_num INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)
```

### جدول course_enrollments

```sql
CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY (course_id, user_id)
)
```

## نکات پیاده‌سازی

- این سیستم از PHP برای بک‌اند و HTML/CSS/JavaScript برای فرانت‌اند استفاده می‌کند.
- برای ذخیره‌سازی داده‌ها از MySQL استفاده شده است.
- برای طراحی رابط کاربری از Bootstrap استفاده شده است.
- API‌ها از فرمت JSON برای تبادل داده استفاده می‌کنند.
- سیستم دارای قابلیت‌های پایه احراز هویت است (در حال حاضر به صورت شبیه‌سازی شده).

## نیازمندی‌ها

- PHP 7.0 یا بالاتر
- MySQL 5.7 یا بالاتر
- وب سرور (Apache یا Nginx)
- مرورگر مدرن با پشتیبانی از JavaScript