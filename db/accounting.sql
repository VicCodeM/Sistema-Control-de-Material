-- Create Expenses Categories table
CREATE TABLE IF NOT EXISTS expense_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create Income Categories table
CREATE TABLE IF NOT EXISTS income_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    payment_method TEXT NOT NULL,
    receipt_number TEXT,
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create Additional Income table (non-tuition income)
CREATE TABLE IF NOT EXISTS additional_income (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    income_date DATE NOT NULL,
    payment_method TEXT NOT NULL,
    reference_number TEXT,
    notes TEXT,
    created_by INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES income_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Create Financial Reports table
CREATE TABLE IF NOT EXISTS financial_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_type TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_income DECIMAL(10,2) NOT NULL,
    total_expenses DECIMAL(10,2) NOT NULL,
    net_profit DECIMAL(10,2) NOT NULL,
    generated_by INTEGER,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Insert default expense categories
INSERT OR IGNORE INTO expense_categories (name, description) VALUES
('Salarios', 'Pagos de nómina al personal'),
('Suministros', 'Material didáctico y suministros generales'),
('Servicios', 'Servicios básicos (luz, agua, etc.)'),
('Mantenimiento', 'Mantenimiento de instalaciones'),
('Alimentación', 'Alimentos y bebidas para los niños'),
('Limpieza', 'Productos y servicios de limpieza'),
('Marketing', 'Gastos de publicidad y promoción'),
('Otros', 'Otros gastos diversos');

-- Insert default income categories
INSERT OR IGNORE INTO income_categories (name, description) VALUES
('Matrículas', 'Pagos de inscripción'),
('Mensualidades', 'Pagos mensuales regulares'),
('Eventos', 'Ingresos por eventos especiales'),
('Materiales', 'Venta de materiales educativos'),
('Uniformes', 'Venta de uniformes'),
('Otros', 'Otros ingresos diversos');