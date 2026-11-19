<?php
/**
 * Starter content inserted the first time the site runs.
 * Everything here can be edited or deleted from the Admin Panel.
 */

function seed_demo_content(PDO $pdo): void
{
    $d = 'assets/img/demo/';

    $settings = [
        'site_name' => 'MD. Mujtuba Rafid',
        'site_alias' => 'rafid0',
        'site_title' => 'R&D Engineer',
        'meta_description' => 'MD. Mujtuba Rafid (rafid0), R&D Engineer building intelligent, reliable and affordable systems with embedded electronics, robotics and industrial automation.',
        'accent_color' => '#22c55e',
        'hero_badge' => 'R&D Engineer',
        'hero_title' => "Robotics.\nAutomation.\nEmbedded Systems.",
        'hero_text' => 'Building intelligent, reliable and affordable systems with Embedded Electronics, Robotics and Industrial Automation technologies.',
        'hero_image' => $d . 'hero-robot.svg',
        'stats' => "35+ | Projects Completed\n12+ | Industrial Systems\n8 | Research Papers\n5 | Awards & Achievements\n4+ | Years of Experience\n1000+ | Hours of Development",
        'expertise' => "Embedded Systems | Low-level to high-level system design | cpu\nRobotics & Automation | Perception, control & intelligent automation | robot-arm\nIndustrial Solutions | Real-world, reliable & scalable systems | factory\nAI & Computer Vision | Intelligent perception & machine learning | target",
        'about_photo' => $d . 'profile.svg',
        'about_short' => 'I design and build intelligent systems that bridge research and real-world applications — from embedded hardware to autonomous robots and AI-powered automation.',
        'about_bio' => "I am an R&D Engineer focused on robotics, industrial automation and embedded systems. I enjoy taking an idea from a sketch on paper to a working, field-tested product: designing the electronics, writing the firmware, building the mechanics and integrating computer vision and machine learning where it adds value.\n\nMy work spans agricultural robotics for the poultry industry, wearable sensing for assistive technology, renewable-energy systems and industrial controllers. I care about building systems that are reliable, affordable and easy to maintain, so they can make a real difference outside the lab.",
        'experience' => "2023 – Present | R&D Engineer | Your Company Name | Leading design and development of robotics and automation systems for agriculture and industry.\n2021 – 2023 | Embedded Systems Engineer | Previous Company | Designed firmware and PCBs for IoT and industrial control products.\n2020 – 2021 | Research Assistant | University Robotics Lab | Worked on wearable sensing and gesture recognition research.",
        'education' => "2016 – 2020 | B.Sc. in Electrical & Electronic Engineering | Your University | Thesis on wearable sensor systems.",
        'skills' => "Embedded: ESP32, STM32, Arduino, FreeRTOS, PCB Design\nRobotics: ROS, Jetson Nano, Motion Control, Kinematics\nSoftware: Python, C/C++, OpenCV, Machine Learning\nIndustrial: PLC, HMI, Modbus, Power Electronics\nTools: KiCad, SolidWorks, Fusion 360, Git, Linux",
        'achievements' => "2025 | Journal publication in IEEE Access | Bilateral Wearable Data-Glove for Bangla Sign Language\n2024 | Patent application filed | Smart Poultry Feeder with Health Monitoring\n2023 | Innovation Award | National robotics & automation competition",
        'cv_file' => '',
        'cv_updated' => '',
        'contact_email' => '',
        'contact_phone' => '',
        'contact_location' => 'Dhaka, Bangladesh',
        'availability' => 'Open to R&D collaborations, research projects and engineering roles.',
        'social_linkedin' => 'https://www.linkedin.com/',
        'social_github' => 'https://github.com/',
        'social_youtube' => '',
        'social_instagram' => '',
        'social_facebook' => '',
        'social_x' => '',
        'social_scholar' => '',
        'social_researchgate' => '',
        'footer_text' => 'Designing and building intelligent systems for a better tomorrow.',
        'notify_email' => '1',
        'logo' => '',
    ];
    $st = $pdo->prepare('INSERT OR IGNORE INTO settings(key, value) VALUES(?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    $projects = [
        [
            'slug' => 'egg-sorting-robot',
            'title' => 'Egg Sorting Robot',
            'subtitle' => 'Automated egg sorting and grading system',
            'category' => 'Robotics, Automation',
            'tags' => 'ROS, Jetson Nano, Python, OpenCV, Arduino',
            'summary' => 'An automated system that sorts eggs based on size and quality using computer vision and robotic manipulation.',
            'overview' => 'The Egg Sorting Robot is an automated system that sorts eggs based on size and quality using computer vision and robotic manipulation.',
            'features' => "Automatic egg detection and grading\nHigh accuracy sorting mechanism\nReal-time monitoring with web dashboard\nModular and scalable design\nLow cost and easy maintenance",
            'technologies' => "Jetson Nano\nPython\nOpenCV\nArduino\nROS\nConveyor System\nMG996R Servo\nDC Motors\nPower Electronics",
            'specs' => "Input: Mixed Size Eggs\nGrading: 3 Grades (Small, Medium, Large)\nAccuracy: > 95%\nThroughput: ~ 3600 eggs / hour\nPower Supply: 24V DC\nDimensions: 1200 x 600 x 700 mm\nWeight: ~ 45 Kg",
            'results' => "95.6% | Sorting Accuracy\n3600 | Eggs / Hour\n3 | Grading Levels\n24/7 | Tested Operation",
            'chart_data' => '88, 91, 90, 93, 92, 94, 93, 95, 94, 95, 96, 95, 96, 95, 97, 96',
            'chart_label' => 'Accuracy (%) across test runs',
            'details' => "**Problem.** Manual egg grading is slow, inconsistent and labour intensive for small and medium poultry farms.\n\n**Approach.** A camera above the conveyor captures each egg. A Jetson Nano runs an OpenCV pipeline to measure size and detect cracks or dirt. ROS coordinates the conveyor, gripper and robotic arm, which places each egg in the correct grade tray.\n\n**Outcome.** The prototype reached 95.6% sorting accuracy at around 3600 eggs per hour in continuous testing.",
            'cover_image' => $d . 'egg-sorting.svg',
            'diagram_image' => $d . 'block-diagram.svg',
            'completed_date' => '2024-05',
            'team_size' => '3 Members',
            'duration' => '4 Months',
            'status' => 'Completed',
            'demo_url' => '',
            'video_url' => '',
            'featured' => 1,
            'sort_order' => 1,
        ],
        [
            'slug' => 'bilateral-data-glove-platform',
            'title' => 'Bilateral Data-Glove Platform',
            'subtitle' => 'Real-time bilateral teleoperation and gesture capture system for robotics and haptics research',
            'category' => 'Robotics, Embedded Systems',
            'tags' => 'ESP32-S3, IMU, ADS1115, FreeRTOS, UDP, Python',
            'summary' => 'Wearable data-gloves that capture finger and hand movement in real time for teleoperation and gesture recognition.',
            'overview' => 'The Bilateral Data-Glove Platform enables real-time capture and transmission of finger and hand movements for bilateral teleoperation. It is designed for low-latency, wired/wireless communication and precise motion mapping.',
            'features' => "High precision IMU based motion tracking\nForce feedback ready architecture\nLow latency UDP communication\nModular and wearable design\nCross-platform support (Windows / Linux)\nOpen-source firmware and tools",
            'technologies' => "ESP32-S3\nADS1115 ADC\nICM-20948 IMU\nFreeRTOS\nUDP Protocol\nPython\nC / C++",
            'specs' => "Sensors per hand: 5 flex + 1 9-axis IMU\nSampling rate: 100 Hz\nLatency: < 15 ms\nConnectivity: Wi-Fi (UDP) / USB\nBattery: 3.7V Li-ion, ~6 hours",
            'results' => "< 15 ms | Latency\n100 Hz | Sample Rate\n2 | Hands Tracked\n6 h | Battery Life",
            'chart_data' => '',
            'chart_label' => '',
            'details' => '',
            'cover_image' => $d . 'data-glove.svg',
            'diagram_image' => '',
            'completed_date' => '2024-05',
            'team_size' => '2 Members',
            'duration' => '3 Months',
            'status' => 'Completed',
            'featured' => 1,
            'sort_order' => 2,
        ],
        [
            'slug' => 'poultry-biosecurity-robot',
            'title' => 'Poultry Biosecurity Robot',
            'subtitle' => 'Autonomous rover for disinfection and monitoring inside poultry farms',
            'category' => 'Robotics, Agriculture',
            'tags' => 'Jetson Nano, ROS, Automation, Computer Vision',
            'summary' => 'An autonomous ground robot that patrols poultry sheds, monitors bird health and sprays disinfectant on a schedule.',
            'overview' => 'An autonomous ground robot that navigates poultry sheds, monitors environmental conditions and bird behaviour, and performs scheduled disinfection to improve farm biosecurity.',
            'features' => "Autonomous navigation between rows\nScheduled disinfectant spraying\nTemperature, humidity and ammonia monitoring\nCamera-based dead bird detection\nRemote dashboard and alerts",
            'technologies' => "Jetson Nano\nROS\nRaspberry Pi 4\nOpenCV\nLiDAR\nDC Motor Drivers",
            'specs' => "Drive: 4WD differential\nPayload: 20 L tank\nRuntime: ~4 hours\nNavigation: LiDAR + line following",
            'results' => '',
            'cover_image' => $d . 'biosecurity-robot.svg',
            'completed_date' => '2024-03',
            'team_size' => '4 Members',
            'duration' => '6 Months',
            'status' => 'Completed',
            'featured' => 1,
            'sort_order' => 3,
        ],
        [
            'slug' => 'solar-street-light',
            'title' => 'Solar Street Light',
            'subtitle' => 'Smart IoT solar street light with battery management',
            'category' => 'IoT, Renewable Energy',
            'tags' => 'Solar, IoT, Battery, LoRa',
            'summary' => 'A smart solar street light with MPPT charging, adaptive dimming and remote monitoring over LoRa.',
            'overview' => 'A smart solar street light with MPPT charging, motion-based adaptive dimming and remote monitoring over a LoRa network.',
            'features' => "MPPT solar charge controller\nMotion-based adaptive dimming\nBattery health monitoring\nRemote monitoring over LoRa",
            'technologies' => "STM32\nLoRa\nMPPT\nLiFePO4 Battery\nPIR Sensor",
            'specs' => "Panel: 100 W\nBattery: 12V 40Ah LiFePO4\nLamp: 30 W LED\nAutonomy: 3 nights",
            'results' => '',
            'cover_image' => $d . 'solar-light.svg',
            'completed_date' => '2024-04',
            'team_size' => '2 Members',
            'duration' => '2 Months',
            'status' => 'Completed',
            'featured' => 1,
            'sort_order' => 4,
        ],
        [
            'slug' => 'industrial-controller',
            'title' => 'Industrial Controller',
            'subtitle' => 'STM32 based PLC-style controller with HMI',
            'category' => 'Industrial Automation',
            'tags' => 'STM32, PLC, HMI, Modbus',
            'summary' => 'A rugged STM32-based industrial controller with isolated I/O, Modbus RTU and an HMI touch panel.',
            'overview' => 'A rugged STM32-based industrial controller with isolated digital/analog I/O, Modbus RTU/TCP and a touch-screen HMI for machine control.',
            'features' => "Isolated 24V digital I/O\n4–20 mA analog inputs\nModbus RTU / TCP\nTouch-screen HMI",
            'technologies' => "STM32\nModbus\nHMI\nKiCad",
            'cover_image' => $d . 'industrial-controller.svg',
            'completed_date' => '2024-04',
            'team_size' => '2 Members',
            'duration' => '3 Months',
            'status' => 'Completed',
            'featured' => 0,
            'sort_order' => 5,
        ],
        [
            'slug' => 'smart-poultry-feeder',
            'title' => 'Smart Poultry Feeder',
            'subtitle' => 'IoT feeder with health monitoring',
            'category' => 'IoT, Automation',
            'tags' => 'ESP32, IoT, Load Cell, Automation',
            'summary' => 'An automatic feeder that dispenses measured feed on schedule and tracks consumption to flag health issues early.',
            'overview' => 'An automatic feeder that dispenses measured feed on a schedule and tracks consumption patterns to flag possible health issues early.',
            'features' => "Scheduled, measured feed dispensing\nConsumption tracking with load cells\nMobile notifications",
            'technologies' => "ESP32\nLoad Cell\nStepper Motor\nMQTT",
            'cover_image' => $d . 'poultry-feeder.svg',
            'completed_date' => '2024-01',
            'team_size' => '3 Members',
            'duration' => '4 Months',
            'status' => 'Completed',
            'featured' => 0,
            'sort_order' => 6,
        ],
        [
            'slug' => 'automated-poultry-shed',
            'title' => 'Automated Poultry Shed',
            'subtitle' => 'Environment control for poultry houses',
            'category' => 'Industrial Automation',
            'tags' => 'PLC, Sensors, Environment Control',
            'summary' => 'Climate control for poultry houses: ventilation, cooling and lighting driven by sensor feedback.',
            'overview' => 'An environment control system for poultry houses that drives ventilation, cooling pads and lighting automatically from temperature, humidity and ammonia sensors.',
            'features' => "Automatic ventilation and cooling\nLighting schedules\nAlarm on unsafe conditions",
            'technologies' => "PLC\nHMI\nSensors\nVFD",
            'cover_image' => $d . 'poultry-shed.svg',
            'completed_date' => '2023-11',
            'team_size' => '3 Members',
            'duration' => '5 Months',
            'status' => 'Completed',
            'featured' => 0,
            'sort_order' => 7,
        ],
    ];

    $cols = ['slug', 'title', 'subtitle', 'category', 'tags', 'summary', 'overview', 'features', 'technologies', 'specs',
        'results', 'chart_data', 'chart_label', 'details', 'cover_image', 'diagram_image', 'completed_date', 'team_size',
        'duration', 'status', 'demo_url', 'video_url', 'featured', 'sort_order'];
    $sql = 'INSERT INTO projects(' . implode(',', $cols) . ') VALUES(' . implode(',', array_fill(0, count($cols), '?')) . ')';
    $ins = $pdo->prepare($sql);
    foreach ($projects as $p) {
        $row = [];
        foreach ($cols as $c) {
            $row[] = $p[$c] ?? (in_array($c, ['featured', 'sort_order'], true) ? 0 : '');
        }
        $ins->execute($row);
    }

    // Demo gallery images for the first project.
    $eggId = (int) $pdo->query("SELECT id FROM projects WHERE slug = 'egg-sorting-robot'")->fetchColumn();
    $media = $pdo->prepare('INSERT INTO project_media(project_id, type, file_path, title, sort_order) VALUES(?,?,?,?,?)');
    $i = 0;
    foreach (['egg-sorting.svg' => 'Full system', 'poultry-feeder.svg' => 'Egg tray', 'industrial-controller.svg' => 'Control electronics', 'block-diagram.svg' => 'System architecture'] as $file => $title) {
        $media->execute([$eggId, 'image', $d . $file, $title, $i++]);
    }

    $research = [
        ['bilateral-wearable-data-glove-bangla-sign-language', 'Bilateral Wearable Data-Glove for Bangla Sign Language', 'Published', 'IEEE Access', '2025',
            'A two-handed wearable sensing glove and machine-learning pipeline that recognises Bangla Sign Language gestures in real time.',
            $d . 'data-glove.svg', 1, 1],
        ['smart-poultry-feeder-health-monitoring', 'Smart Poultry Feeder with Health Monitoring', 'Patent Filed', 'Patent Application', '2024',
            'An automatic feeder that tracks feed consumption per pen and flags abnormal patterns as an early warning of disease.',
            $d . 'poultry-feeder.svg', 1, 2],
        ['autonomous-poultry-farm-management-system', 'Autonomous Poultry Farm Management System', 'In Progress', 'Research & Development', '2025',
            'An integrated platform combining mobile robots, environment sensing and analytics to automate day-to-day poultry farm management.',
            $d . 'farm-system.svg', 1, 3],
    ];
    $r = $pdo->prepare('INSERT INTO research(slug, title, status, venue, year, summary, body, image, featured, sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)');
    foreach ($research as $x) {
        $r->execute([$x[0], $x[1], $x[2], $x[3], $x[4], $x[5], $x[5], $x[6], $x[7], $x[8]]);
    }

    $g = $pdo->prepare('INSERT INTO gallery(title, category, file_path, sort_order) VALUES(?,?,?,?)');
    $g->execute(['Robotics lab', 'Lab', $d . 'hero-robot.svg', 1]);
    $g->execute(['Field testing', 'Field Work', $d . 'biosecurity-robot.svg', 2]);
}
