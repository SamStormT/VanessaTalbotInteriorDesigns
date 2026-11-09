<?php
/**
 * Media Library: storing uploads, searching, usage checks and deletion.
 *
 * Uploads are identified from their actual content, never their name or the
 * browser's claim. Files get a generated name, so an upload can't overwrite
 * anything or choose its own extension. SVG is refused because it can carry
 * scripts.
 */

declare(strict_types=1);

const MEDIA_MAX_BYTES = 10485760; // 10 MB
const MEDIA_TYPES     = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

/** Check a file from $_FILES and store it. Returns the new MediaLibrary row. */
function media_store_upload(array $file, string $category): array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (is_array($error)) {
        throw new RuntimeException('Please upload one file at a time.');
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException(media_too_large_message());
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('That file could not be uploaded. Please try again.');
    }
    if ((int) $file['size'] > MEDIA_MAX_BYTES) {
        throw new RuntimeException(media_too_large_message());
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $info = @getimagesize($file['tmp_name']);
    if (!isset(MEDIA_TYPES[$mime]) || $info === false || $info['mime'] !== $mime) {
        throw new RuntimeException('Please choose a JPG, PNG, WebP or GIF image.');
    }

    $originalName = mb_substr(basename(str_replace('\\', '/', (string) $file['name'])), 0, 255);
    $stem         = strtolower((string) pathinfo($originalName, PATHINFO_FILENAME));
    $stem         = substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', $stem), '-'), 0, 60) ?: 'image';
    $folder       = 'uploads/media/' . date('Y/m');
    $path         = $folder . '/' . $stem . '-' . bin2hex(random_bytes(4)) . '.' . MEDIA_TYPES[$mime];
    $absolute     = ROOT_PATH . '/' . $path;

    if (!is_dir(ROOT_PATH . '/' . $folder) && !mkdir(ROOT_PATH . '/' . $folder, 0755, true) && !is_dir(ROOT_PATH . '/' . $folder)) {
        error_log('Could not create upload folder: ' . $folder);
        throw new RuntimeException('That file could not be saved. Please try again.');
    }
    if (!move_uploaded_file($file['tmp_name'], $absolute)) {
        error_log('Could not save upload: ' . $path);
        throw new RuntimeException('That file could not be saved. Please try again.');
    }

    try {
        db_query(
            'INSERT INTO MediaLibrary (FileName, FilePath, FileType, FileSize, Width, Height, Category, UploadedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$originalName, $path, $mime, (int) $file['size'], (int) $info[0], (int) $info[1], $category === '' ? null : mb_substr($category, 0, 100), current_admin()['AdminUserID']]
        );
        $id = (int) db()->lastInsertId();
        activity_log('upload', 'MediaLibrary', $id);
    } catch (Throwable $failure) {
        @unlink($absolute);
        throw $failure;
    }

    return db_row('SELECT * FROM MediaLibrary WHERE MediaID = ?', [$id]);
}

function media_too_large_message(): string
{
    return 'That image is too large. The limit is ' . (int) (MEDIA_MAX_BYTES / 1048576) . ' MB.';
}

/** The fields the browser needs for one image. */
function media_public(array $row): array
{
    return [
        'id'      => (int) $row['MediaID'],
        'path'    => $row['FilePath'],
        'url'     => url($row['FilePath']),
        'name'    => $row['FileName'],
        'alt'     => (string) ($row['AltText'] ?? ''),
        'caption' => (string) ($row['Caption'] ?? ''),
        'width'   => (int) ($row['Width'] ?? 0),
        'height'  => (int) ($row['Height'] ?? 0),
    ];
}

/** Search by name, alt text, caption or category. Newest first. */
function media_search(string $query, int $limit, int $offset, ?string $category = null): array
{
    [$where, $params] = media_where($query, $category);
    return db_all("SELECT * FROM MediaLibrary $where ORDER BY MediaID DESC LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $offset), $params);
}

function media_count(string $query, ?string $category = null): int
{
    [$where, $params] = media_where($query, $category);
    return (int) db_value("SELECT COUNT(*) FROM MediaLibrary $where", $params);
}

function media_where(string $query, ?string $category): array
{
    $clauses = [];
    $params  = [];
    if (trim($query) !== '') {
        $like      = '%' . addcslashes(trim($query), '\\%_') . '%';
        $clauses[] = '(FileName LIKE ? OR AltText LIKE ? OR Caption LIKE ? OR Category LIKE ?)';
        array_push($params, $like, $like, $like, $like);
    }
    if ($category !== null && $category !== '') {
        $clauses[] = 'Category = ?';
        $params[]  = $category;
    }
    return [$clauses === [] ? '' : 'WHERE ' . implode(' AND ', $clauses), $params];
}

/** Where a file is in use, as labels people can read. Empty means safe to delete. */
function media_usage(string $path): array
{
    $places = [
        ['HomePageContent', 'HeroImage', 'Homepage hero'],
        ['HomePageContent', 'IntroductionImage', 'Homepage introduction'],
        ['AboutContent', 'PortraitImage', 'About portrait'],
        ['Services', 'FeaturedImage', 'Services'],
        ['PageSectionItems', 'Image', 'Page section items'],
        ['PageSections', 'Image', 'Page sections'],
        ['PortfolioProjects', 'FeaturedImage', 'Portfolio projects'],
        ['ProjectImages', 'ImagePath', 'Project galleries'],
        ['Collaborators', 'Logo', 'Collaborators'],
        ['SpeakingEngagements', 'FeaturedImage', 'Speaking engagements'],
        ['Testimonials', 'Photo', 'Testimonials'],
        ['BlogPosts', 'FeaturedImage', 'Blog posts'],
        ['PhotographyProducts', 'PreviewImage', 'Photography products'],
    ];

    $used = [];
    foreach ($places as [$table, $column, $label]) {
        $count = (int) db_value("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?", [$path]);
        if ($count > 0) {
            $used[] = $count > 1 ? "$label ($count)" : $label;
        }
    }
    return $used;
}

/** Remove the record and its file. Callers must check media_usage() first. */
function media_delete(array $row): void
{
    db_query('DELETE FROM MediaLibrary WHERE MediaID = ?', [$row['MediaID']]);
    activity_log('delete', 'MediaLibrary', (int) $row['MediaID']);

    $uploads = realpath(ROOT_PATH . '/uploads');
    $file    = realpath(ROOT_PATH . '/' . $row['FilePath']);
    if ($uploads !== false && $file !== false && str_starts_with($file, $uploads . DIRECTORY_SEPARATOR)) {
        @unlink($file);
    }
}

/** A saved description fills in a library image that has none, ready for reuse. */
function media_learn_alt_text(array $module, array $data): void
{
    foreach ($module['fields'] as $name => $field) {
        if ($field['type'] !== 'image' || empty($field['alt_field']) || empty($data[$name]) || empty($data[$field['alt_field']])) {
            continue;
        }
        db_query(
            "UPDATE MediaLibrary SET AltText = ? WHERE FilePath = ? AND (AltText IS NULL OR AltText = '')",
            [$data[$field['alt_field']], $data[$name]]
        );
    }
}

function media_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Sign-in, permission and CSRF checks that answer in JSON, for browser requests. */
function media_guard_json(string $permission): void
{
    if (!is_logged_in()) {
        media_json(['ok' => false, 'message' => 'You have been signed out. Please sign in again.'], 401);
    }
    if (!can($permission)) {
        activity_log('access_denied', null, null, $permission . ' at ' . current_path());
        media_json(['ok' => false, 'message' => 'Your account does not have permission to do this.'], 403);
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_valid()) {
        media_json(['ok' => false, 'message' => 'This page expired. Please refresh it and try again.'], 403);
    }
}
