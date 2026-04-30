<?php

namespace Database\Seeders;

use App\Models\MissionFaq;
use App\Models\MissionFaqCategory;
use Illuminate\Database\Seeder;

class MissionFaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $faqs = [
            [
                // Spiritual Maturity
                'category' => 'Spiritual Maturity',
                'entries' => [
                    [
                        'question' => 'When does spiritual maturity begin?',
                        'answer' => 'Spiritual maturity begins with a personal relationship with God, marked by surrender to Jesus Christ and daily growth in faith and character.',
                    ],
                    [
                        'question' => 'Now that I have been born again, what’s next?',
                        'answer' => 'After accepting Christ, your spiritual growth continues through obedience to His word, prayer, fellowship, and serving others. Developing a deeper relationship with God is crucial.',
                    ],
                    [
                        'question' => 'Why do we need to mature spiritually?',
                        'answer' => 'Spiritual maturity allows us to know God more intimately, become more like Jesus, discover our God-given purpose, resist temptation, foster healthy relationships, and prepare for eternal life with God.',
                    ],
                    [
                        'question' => 'How can I grow spiritually?',
                        'answer' => 'Spiritual growth involves obedience to God’s commands, prayer, reading and meditating on the Word of God, fellowship with other believers, serving others, trusting God in difficult times, and being a witness to Christ in daily life.',
                    ],
                    [
                        'question' => 'What happens when I feel like I have stumbled or sinned?',
                        'answer' => 'Acknowledge your wrongdoing, seek God’s forgiveness, and trust that His grace is sufficient to restore you. Stay accountable and seek support from fellow believers as you grow stronger in faith.',
                    ],
                    [
                        'question' => 'Is spiritual growth a one-time event or a process?',
                        'answer' => 'Spiritual growth is a lifelong journey. It requires consistent effort, patience, and reliance on God to grow closer to Him and become more Christ-like.',
                    ],
                    [
                        'question' => 'How can I develop a system to grow in my understanding of godliness?',
                        'answer' => 'You can grow in godliness by establishing a regular practice of prayer, Bible study, fellowship, and service, and maintaining an active relationship with God through obedience and trust.',
                    ],
                    [
                        'question' => 'What is the difference between Christianity and Spirituality?',
                        'answer' => 'Christianity refers to the religion based on the life and teachings of Jesus Christ. Spirituality, particularly after Pentecost, refers to a personal and intimate relationship with God, involving deep fellowship and sonship with Him.',
                    ],
                    [
                        'question' => 'How can I live out my faith in everyday life?',
                        'answer' => 'Living out your faith involves making decisions based on God’s Word, showing Christ in your actions, serving others, and being a witness to those around you.',
                    ],
                    [
                        'question' => 'Why is it important to abide in Christ?',
                        'answer' => 'Abiding in Christ is essential to spiritual growth. As we remain connected to Him, we bear fruit, which reflects His character and draws others to the kingdom.',
                    ],
                    [
                        'question' => 'How can I resist temptations and distractions of the world?',
                        'answer' => 'By maturing spiritually, staying grounded in God’s Word, and relying on His strength, you can overcome temptations and remain focused on your faith.',
                    ],
                    [
                        'question' => 'How does spiritual maturity affect my relationships?',
                        'answer' => 'Spiritual maturity fosters qualities like compassion, forgiveness, and selflessness, which are essential for building and maintaining healthy relationships.',
                    ],
                    [
                        'question' => 'What role does fellowship play in my spiritual growth?',
                        'answer' => 'Fellowship with other believers provides support, encouragement, and accountability, which are key to growing spiritually and staying strong in your faith.',
                    ],
                    [
                        'question' => 'How do I maintain my faith during difficult times?',
                        'answer' => 'Trust God through hardships, knowing that trials produce perseverance and strengthen your faith. Refuse to compromise, even when circumstances are tough.',
                    ],
                    [
                        'question' => 'What does it mean to be a witness of Christ?',
                        'answer' => 'Being a witness means using your life, resources, and actions to glorify Christ and advance His kingdom, reflecting His love and truth in all you do.',
                    ],
                ],
            ],

            // The Holy Spirit
            [
                'category' => 'The Holy Spirit',
                'entries' => [
                    [
                        'question' => 'Why is there so much confusion about the Holy Spirit?',
                        'answer' => 'There is confusion about the Holy Spirit due to a lack of teaching, even among Pentecostal and charismatic congregations, which are often expected to know more about Him. Additionally, many Christians rely on their feelings and experiences instead of the Word of God for knowledge about the Holy Spirit.',
                    ],
                    [
                        'question' => 'Why do some Christian congregations misunderstand the Holy Spirit\'s role?',
                        'answer' => "Some congregations have faulty doctrines about the Holy Spirit, and others may not emphasize teachings about Him. This leads to misconceptions and misuse of the Holy Spirit's identity and works.",
                    ],
                    [
                        'question' => 'Why is it important for Christians to have a biblical understanding of the Holy Spirit?',
                        'answer' => 'Having a biblical understanding of the Holy Spirit is crucial because Christians cannot live Christlike lives without the help of the Holy Spirit. Misunderstanding Him can hinder a believer’s ability to follow Christ effectively.',
                    ],
                    [
                        'question' => "What does George Barna's study say about Christians' understanding of the Holy Spirit?",
                        'answer' => 'George Barna’s study concluded that a small minority of people who identify as Christians possess a biblical worldview regarding the Holy Spirit, indicating widespread misunderstanding.',
                    ],
                    [
                        'question' => 'How does Costi W. Hinn describe the misuse of the Holy Spirit in his book?',
                        'answer' => 'Costi W. Hinn states that the Holy Spirit is possibly the most used and abused member of the Trinity, being misunderstood or misrepresented by many.',
                    ],
                    [
                        'question' => 'Who is the Holy Spirit according to the Bible?',
                        'answer' => 'The Holy Spirit is the third person of the Trinity, fully God, distinct in person but one in essence with the Father and the Son. He has a mind, emotions, and a will, making Him a divine person, not a mere force.',
                    ],
                    [
                        'question' => 'How does the Holy Spirit fit into the doctrine of the Trinity?',
                        'answer' => 'The Holy Spirit is one of the three Persons of the Trinity: Father, Son, and Holy Spirit. Each Person is distinct yet fully God, and there is only one God. The Holy Spirit has His own center of consciousness and relates personally to the Father and the Son.',
                    ],
                    [
                        'question' => 'Is the Holy Spirit a person or just a force?',
                        'answer' => 'The Holy Spirit is a person, not a force. He has the attributes of a person, such as the ability to think, feel, and make decisions. He can be grieved (Ephesians 4:30) and makes decisions (1 Corinthians 12:7-11).',
                    ],
                    [
                        'question' => 'What scriptures support the personhood and deity of the Holy Spirit?',
                        'answer' => <<<'EOT'
                    The Holy Spirit thinks and knows (1 Corinthians 2:10).
                    He can be grieved (Ephesians 4:30).
                    He intercedes (Romans 8:26-27).
                    He is omnipresent (Psalm 139:7-8), omniscient (1 Corinthians 2:10-11), omnipotent (Luke 1:35), and eternal (Hebrews 9:14).
                EOT,
                    ],
                    [
                        'question' => 'What are some historical misconceptions about the Holy Spirit?',
                        'answer' => <<<'EOT'
                    Some misconceptions include:
                        Sabellianism teaches that the Holy Spirit is not a person but an impersonal force.
                        Arianism, denies the deity of the Holy Spirit, claiming He is a created being rather than fully God.
                EOT,
                    ],
                    [
                        'question' => 'What is Sabellianism and how does it misrepresent the Holy Spirit?',
                        'answer' => 'Sabellianism teaches that the Holy Spirit is merely an expression or manifestation of God, not a distinct person. This view denies the personhood of the Holy Spirit.',
                    ],
                    [
                        'question' => 'What is Arianism and what does it teach about the Holy Spirit?',
                        'answer' => 'Arianism teaches that the Holy Spirit, along with Jesus, is a created being and not of the same divine nature as God the Father. It denies the full deity of the Holy Spirit.',
                    ],
                    [
                        'question' => 'What is the role of the Holy Spirit in creation?',
                        'answer' => 'The Holy Spirit was involved in creation. Psalm 33:6 shows that the Holy Spirit was present in the creation of the material universe, and Psalm 104:29-30 shows He maintains living creatures.',
                    ],
                    [
                        'question' => 'How does the Holy Spirit work in the lives of believers?',
                        'answer' => 'The Holy Spirit convicts believers of sin, regenerates them, sets them free from the power of sin, guides them into truth, teaches them all things, and brings forth Christlike character in them. He also reminds believers of what Jesus taught and empowers them for their callings.',
                    ],
                    [
                        'question' => 'What is the Holy Spirit’s role in convicting people of sin?',
                        'answer' => 'The Holy Spirit convicts the world of sin, righteousness, and judgment (John 16:8). He brings conviction by revealing the truth about sin, as seen when the crowd in Acts 2:37 was convicted after hearing Peter’s sermon.',
                    ],
                    [
                        'question' => 'Does the Holy Spirit only work in preachers and ministers?',
                        'answer' => 'No, the Holy Spirit’s work is not limited to preachers or full-time ministers. He is available to all believers, regardless of their profession or vocation.',
                    ],
                    [
                        'question' => 'What is baptism in the Holy Spirit?',
                        'answer' => "Baptism in the Holy Spirit refers to an experience where the believer is filled with the Holy Spirit. It involves both an outward experience of being immersed in the Holy Spirit's power and an inward reception of His presence and power.",
                    ],
                    [
                        'question' => 'How does the New Testament describe Holy Spirit baptism?',
                        'answer' => "The New Testament describes it as being baptized, filled, or receiving the Holy Spirit (Acts 2:4, Acts 8:15-17). The baptizer is Jesus, and this experience immerses the believer in the Holy Spirit's presence.",
                    ],
                    [
                        'question' => 'What are the different terms used to describe baptism in the Holy Spirit?',
                        'answer' => <<<'EOT'
                    Terms like "being filled with the Holy Spirit," "drinking of the Spirit," and "receiving the Holy Spirit" are used interchangeably to describe baptism in the Holy Spirit.
                EOT,
                    ],
                    [
                        'question' => 'What is the difference between the outward and inward aspects of Holy Spirit baptism?',
                        'answer' => "The outward aspect involves the Holy Spirit's power coming upon a believer, while the inward aspect involves the Holy Spirit’s presence dwelling within the believer, empowering them from within.",
                    ],
                    [
                        'question' => 'Is Holy Spirit baptism a one-time event?',
                        'answer' => 'No, baptism in the Holy Spirit is not limited to one-time events. While some believe it happened only for the apostles, examples in the Bible show that ordinary believers, like Ananias, were involved in passing on this experience (Acts 9:17).',
                    ],
                    [
                        'question' => 'Did Holy Spirit baptism end with the apostles?',
                        'answer' => 'No, the baptism of the Holy Spirit did not end with the apostles. The Bible records instances where believers beyond the apostles received the Holy Spirit, showing that it is for all Christians.',
                    ],
                    [
                        'question' => 'How can I receive the baptism in the Holy Spirit?',
                        'answer' => <<<'EOT'
                    To receive the baptism in the Holy Spirit, one must:
                        Be born again (repent of sins).
                        Thirst for the Holy Spirit (John 7:37-39).
                        Understand that the Holy Spirit is God's gift.
                        Ask and receive by faith (Luke 11:9-13).
                EOT,
                    ],
                    [
                        'question' => 'What is the process of being filled with the Holy Spirit?',
                        'answer' => 'Being filled with the Holy Spirit involves repenting, thirsting for His presence, believing in His promise, and asking by faith to receive His infilling.',
                    ],
                    [
                        'question' => 'Do I need to be baptised in the Holy Spirit if I’m already a Christian?',
                        'answer' => 'Yes, the New Testament shows two separate experiences of receiving the Holy Spirit. At conversion, a believer is indwelt by the Holy Spirit, but the baptism in the Holy Spirit is a subsequent experience that empowers for service and boldness.',
                    ],
                    [
                        'question' => 'What are the conditions for being filled with the Holy Spirit?',
                        'answer' => 'The conditions for being filled with the Holy Spirit include being born again, thirsting for Him, and asking in faith.',
                    ],
                    [
                        'question' => 'Is thirsting for the Holy Spirit necessary?',
                        'answer' => 'Yes, thirsting for the Holy Spirit is necessary, as Jesus said in John 7:37-39 that those who are thirsty should come to Him and drink, referring to the Holy Spirit.',
                    ],
                    [
                        'question' => 'What are the signs that someone has been baptized in the Holy Spirit?',
                        'answer' => 'Signs include speaking in tongues, power for witnessing, boldness in preaching, joy, and the fruit of the Spirit.',
                    ],
                    [
                        'question' => 'Is speaking in tongues the only evidence of Holy Spirit baptism?',
                        'answer' => 'No, while speaking in tongues is often considered initial evidence, other signs include boldness (Acts 4:31), power (Acts 1:8), and joy (Acts 13:52).',
                    ],
                    [
                        'question' => 'How does the Holy Spirit give believers boldness and courage?',
                        'answer' => 'The Holy Spirit gives boldness by empowering believers to witness and speak with authority. For example, Peter, who once denied Jesus, boldly preached after receiving the Holy Spirit.',
                    ],
                    [
                        'question' => 'What other manifestations can follow Holy Spirit baptism?',
                        'answer' => 'Other manifestations include effective witnessing, joy, hearing God clearly, and boldness in faith.',
                    ],
                    [
                        'question' => 'What role does the Holy Spirit play in witnessing and evangelism?',
                        'answer' => 'The Holy Spirit empowers believers to witness effectively, giving them the words to say and the boldness to speak with authority, as seen in Acts 1:8.',
                    ],
                    [
                        'question' => 'How can I grieve or quench the Holy Spirit?',
                        'answer' => 'You can grieve or quench the Holy Spirit by living in sin, resisting His guidance, and ignoring His promptings. Bitterness, anger, and unforgiveness are some actions that grieve the Holy Spirit (Ephesians 4:30).',
                    ],
                    [
                        'question' => 'What does it mean to resist the Holy Spirit?',
                        'answer' => 'Resisting the Holy Spirit means rejecting His conviction, guidance, or prompting. Acts 7:51 warns against resisting the Holy Spirit, as it hardens the heart.',
                    ],
                    [
                        'question' => 'How does sin affect our relationship with the Holy Spirit?',
                        'answer' => 'Sin grieves the Holy Spirit and hinders His work in our lives. It creates distance between the believer and God, affecting spiritual growth and effectiveness in ministry.',
                    ],
                    [
                        'question' => 'What does the Bible say about grieving the Holy Spirit?',
                        'answer' => 'Ephesians 4:30 warns believers not to grieve the Holy Spirit by living in disobedience, bitterness, and anger, as this causes a rift in our relationship with Him.',
                    ],
                    [
                        'question' => 'How can I avoid quenching the Holy Spirit in my life?',
                        'answer' => 'You can avoid quenching the Holy Spirit by living in obedience, staying sensitive to His promptings, and nurturing your spiritual life through prayer and Bible study.',
                    ],
                    [
                        'question' => 'Why do Christians need to be baptized in the Holy Spirit?',
                        'answer' => 'Christians need to be baptized in the Holy Spirit to receive power for witnessing, boldness in sharing the gospel, and the ability to live Christlike lives (Acts 1:8).',
                    ],
                    [
                        'question' => 'What promises did Jesus make about the Holy Spirit?',
                        'answer' => 'Jesus promised that the Holy Spirit would be a Helper, Teacher, and Comforter who would guide believers into all truth (John 14:16-17, John 16:13).',
                    ],
                    [
                        'question' => 'How does the Holy Spirit empower believers for their assignments?',
                        'answer' => 'The Holy Spirit empowers believers by giving them spiritual gifts, boldness, wisdom, and guidance to accomplish the tasks God has called them to.',
                    ],
                    [
                        'question' => 'How does the Holy Spirit help believers understand God’s truth and scriptures?',
                        'answer' => 'The Holy Spirit illuminates God’s Word, teaching and reminding believers of the truths that Jesus taught (John 14:26). He opens the eyes of believers to the depth of Scripture and guides them into all truth.',
                    ],
                    [
                        'question' => 'What gifts does the Holy Spirit give to believers?',
                        'answer' => 'The Holy Spirit gives various gifts, including wisdom, knowledge, faith, healing, miracles, prophecy, discernment, tongues, and the interpretation of tongues (1 Corinthians 12:7-11).',
                    ],
                    [
                        'question' => 'How does the Holy Spirit help us walk in obedience to God’s commands?',
                        'answer' => 'The Holy Spirit strengthens and empowers believers to resist sin and obey God’s commands. He works within us to produce the fruit of the Spirit, leading to Christlike character.',
                    ],
                ],
            ],
            // Cults
            [
                'category' => 'Cults',
                'entries' => [
                    [
                        'question' => 'What is a cult?',
                        'answer' => 'A cult is a system of religious beliefs and rituals regarded as unorthodox or spurious, often involving devotion to a person, idea, or thing. From a Christian perspective, cults can be classified as either Non-Christian Cults or Pseudo-Christian Cults.',
                    ],
                    [
                        'question' => 'How does a Christian identify a cult?',
                        'answer' => 'Cults typically distort Christian doctrines, reduce the Lordship of Jesus, or exalt a human leader above God. Knowing the Bible well is crucial to identifying and avoiding the traps of cults.',
                    ],
                    [
                        'question' => 'What are some characteristics of a cult?',
                        'answer' => 'Characteristics include reducing the Lordship of Christ, exalting the leader, demanding unquestionable obedience, stifling freedom, and rejecting or distorting fundamental Christian beliefs.',
                    ],
                    [
                        'question' => 'How do cults misrepresent Jesus?',
                        'answer' => 'Cults often reduce Jesus to a lesser god or a mere created being, denying His full divinity and humanity.',
                    ],
                    [
                        'question' => 'How do cults treat the Bible?',
                        'answer' => 'Many cults either reject the Bible as the complete Word of God or add other sources of authority, often teachings or writings from their founders.',
                    ],
                    [
                        'question' => 'What Christian doctrines are commonly rejected by cults?',
                        'answer' => 'Cults often reject the Trinity, the deity of Jesus, the virgin birth, salvation by grace, and the existence of hell.',
                    ],
                    [
                        'question' => 'Why is it difficult for a cult member to leave?',
                        'answer' => 'Cults instill fear, demand loyalty to the leader, and control members by distorting scripture or claiming punishment from God for those who leave.',
                    ],
                    [
                        'question' => 'How can we effectively witness to someone in a cult?',
                        'answer' => 'Be gentle, avoid arguments, use the Bible accurately, understand the cult’s beliefs, share your personal testimony, exalt Jesus, and rely on the Holy Spirit for guidance.',
                    ],
                    [
                        'question' => 'What are some examples of Pseudo-Christian cults?',
                        'answer' => 'Examples include Jehovah’s Witnesses, Mormons, Christian Science, and The Way International.',
                    ],
                ],
            ],
            // Occults
            [
                'category' => 'Occults',
                'entries' => [
                    [
                        'question' => 'What is the occult?',
                        'answer' => 'The occult involves the study and practice of supernatural powers or forces, often associated with witchcraft, astrology, divination, and other forbidden spiritual activities.',
                    ],
                    [
                        'question' => 'What does the Bible say about the occult?',
                        'answer' => 'The Bible condemns all occult practices (Deuteronomy 18:9-12), including divination, witchcraft, sorcery, and consulting the dead, calling them detestable to the Lord.',
                    ],
                    [
                        'question' => 'Why are people drawn to the occult?',
                        'answer' => 'People are often attracted to the occult due to curiosity, a search for hidden knowledge, spiritual hunger, or dissatisfaction with traditional religion.',
                    ],
                    [
                        'question' => 'What factors contribute to the rise of occult practices today?',
                        'answer' => 'Factors include globalisation, failure of the church to meet spiritual needs, media influence, biblical illiteracy, and inadequate discipling of new converts.',
                    ],
                    [
                        'question' => 'What are some common practices in the occult?',
                        'answer' => 'Practices include fortune-telling, necromancy (contacting the dead), astrology, sorcery, witchcraft, casting spells, and using tools like Ouija boards.',
                    ],
                    [
                        'question' => 'Can Christians be involved in the occult?',
                        'answer' => 'Some Christians may unknowingly participate in occult practices due to spiritual hunger or ignorance of biblical teachings. It’s essential to have a firm grasp of Scripture to avoid falling into occultism.',
                    ],
                    [
                        'question' => 'How can someone be delivered from occult practices?',
                        'answer' => 'Deliverance comes through the blood of Jesus, the truth of God’s Word, guidance by the Holy Spirit, and putting on the full armour of God (Ephesians 6:10-18).',
                    ],
                    [
                        'question' => 'What role does the media play in promoting the occult?',
                        'answer' => 'The media, especially music, movies, and literature, often glorify occult themes, making occultism seem fascinating or acceptable, especially to younger audiences.',
                    ],
                    [
                        'question' => 'How can Christians guard against the occult?',
                        'answer' => 'Christians should stay grounded in the Word of God, be aware of occult influences, and rely on the Holy Spirit for discernment and protection.',
                    ],
                ],
            ],
            // Technology & the digital space
            [
                'category' => 'Technology & the digital space',
                'entries' => [
                    [
                        'question' => 'Why is technology important for spreading the gospel?',
                        'answer' => 'Technology provides platforms like social media, YouTube, and online forums that allow believers to share the gospel with people worldwide without physical barriers.',
                    ],
                    [
                        'question' => 'How can I use social media to talk about Jesus?',
                        'answer' => 'You can share Bible verses, personal testimonies, or meaningful Christian content on platforms like Instagram, Facebook, and YouTube. You can also engage in conversations about faith with your followers.',
                    ],
                    [
                        'question' => 'Is there a biblical basis for using technology in ministry?',
                        'answer' => 'Yes, in Romans 10:14-15, it is clear that the gospel needs a medium to reach others. Just as Roman roads helped spread the gospel, today, technology can be seen as modern infrastructure that advances God’s kingdom.',
                    ],
                    [
                        'question' => 'Is it okay to trust technology, or should we only rely on the Holy Spirit?',
                        'answer' => 'Technology is a tool that can be used for good or evil. While it helps in spreading the gospel, it should never replace reliance on the Holy Spirit for guidance and wisdom in how it’s used.',
                    ],
                    [
                        'question' => 'What are the dangers of using technology for the gospel?',
                        'answer' => "Propaganda and ungodly content can spread quickly. Additionally, Christian content can be censored or banned. It's important to remain vigilant and thoughtful about how we use these platforms.",
                    ],
                    [
                        'question' => 'Can AI (Artificial Intelligence) be used for God’s work?',
                        'answer' => "AI, like ChatGPT, can answer common questions and assist with gospel dissemination, but it's essential to use discernment. AI should not replace personal, Spirit-led teaching and guidance.",
                    ],
                    [
                        'question' => 'What is Virtual Reality (VR) and how can it be used to teach the Bible?',
                        'answer' => 'VR creates simulated environments. It can be used to create immersive Bible experiences, such as walking through the streets of ancient Israel or interactively exploring stories from Scripture.',
                    ],
                    [
                        'question' => 'What are some examples of technology helping to spread the gospel in the past?',
                        'answer' => 'The Roman road system made it easier for early Christians to travel and spread the gospel. Similarly, the invention of the printing press allowed for the mass distribution of Bibles.',
                    ],
                    [
                        'question' => 'What should I be careful about when using the internet as a Christian?',
                        'answer' => 'Be cautious about ungodly content and how easy it is to get distracted or misled by false teachings. Also, remember that many platforms may have policies that go against Christian values.',
                    ],
                    [
                        'question' => 'How can I prepare for a career in technology that also serves God?',
                        'answer' => 'Look into fields like AI, VR, robotics, and content creation. By becoming proficient in these areas, you can help develop tools or platforms that enable the spread of the gospel and create Christian content that competes with secular media.',
                    ],
                    [
                        'question' => 'What role does infrastructure play in the spread of the gospel?',
                        'answer' => 'Infrastructure, from roads in ancient times to the modern-day internet, plays a crucial role in making it possible to share the gospel with people who are geographically distant.',
                    ],
                    [
                        'question' => 'What are some ways I can use my phone or laptop to spread the gospel?',
                        'answer' => 'You can use messaging apps to encourage friends, share Christian music, videos, and articles, and engage in online Bible studies. These devices give you access to a global audience.',
                    ],
                    [
                        'question' => 'Is there a connection between science and the gospel?',
                        'answer' => 'Yes, many innovations, such as the telephone and the internet, were developed by people who, knowingly or unknowingly, contributed to the spread of the gospel. Science and technology can be seen as tools that God uses for His purposes.',
                    ],
                    [
                        'question' => 'What future technologies can help spread the gospel?',
                        'answer' => 'Technologies like Augmented Reality (AR), Blockchain, and Quantum Computing have potential. For example, AR could create Bible-learning tools, and Blockchain could help preserve resources for Christian missions in times of persecution.',
                    ],
                    [
                        'question' => 'How can I make a difference for Jesus in the field of technology?',
                        'answer' => 'By studying fields like computer science, engineering, or content creation, you can create or influence the technologies of tomorrow to serve the kingdom of God.',
                    ],
                ],
            ],
            // Relationships & Family
            [
                'category' => 'Relationships & Family',
                'entries' => [
                    [
                        'question' => 'What is the definition of a family?',
                        'answer' => 'A family is a group of individuals united by marriage, blood, or adoption, typically living together and interacting in their respective roles such as spouses, parents, children, and siblings.',
                    ],
                    [
                        'question' => 'What does the Bible say about the origin of the family?',
                        'answer' => 'The Bible indicates that the family originated with God creating Adam and Eve in Genesis 1:27. They had children, including Cain and Abel, which highlights the significance of family in God’s creation plan.',
                    ],
                    [
                        'question' => 'What are some common issues families face?',
                        'answer' => 'Common family issues can include jealousy (Cain and Abel), division (Abraham, Isaac, Sarah, Hagar, and Ishmael), and personal conflicts (David and his brothers).',
                    ],
                    [
                        'question' => 'How can we resolve conflicts within our families?',
                        'answer' => 'We can resolve conflicts by praying about everything (Philippians 4:6-7), obeying our parents (Ephesians 6:1-3), and seeking wisdom from God on how to handle issues (James 1:5).',
                    ],
                    [
                        'question' => 'What role does prayer play in family relationships?',
                        'answer' => 'Prayer is essential as it helps us express our feelings, anxieties, and thoughts to God (Psalms 42:5). It allows us to seek peace and guidance amidst family conflicts.',
                    ],
                    [
                        'question' => 'Is it important to obey parents even when we disagree?',
                        'answer' => "Yes, the Bible commands us to obey our parents regardless of our feelings, as stated in Ephesians 6:1-3. Obedience is part of God's plan for family dynamics.",
                    ],
                    [
                        'question' => "What should I do if my parents' behavior is difficult to accept?",
                        'answer' => 'Cover your parents with grace (Genesis 9:20-23), talk to Jesus about your feelings, and seek guidance on how to approach the situation with wisdom (James 1:5).',
                    ],
                    [
                        'question' => 'How can I cultivate a Godly family environment?',
                        'answer' => 'By studying God’s word (Romans 8:28), fostering deeper relationships with Christ (Psalm 63:1), and pointing each family member to Christ for guidance.',
                    ],
                    [
                        'question' => 'My parents call me names. What should I do?',
                        'answer' => 'It’s important to talk to someone you trust about how it makes you feel. Also, remind yourself of your worth in Christ and seek to communicate openly with your parents.',
                    ],
                    [
                        'question' => 'How can you have a good relationship with your parents?',
                        'answer' => 'Communication is key. Regularly talk to your parents, show appreciation for them, and seek to understand their perspectives.',
                    ],
                    [
                        'question' => 'My father uses drugs and I am stressed about it. What should I do?',
                        'answer' => "It's crucial to talk to a trusted adult about your situation for support. Consider seeking counselling for yourself and encouraging your father to get help.",
                    ],
                    [
                        'question' => 'My parents are always quarrelling, and it affects me. What should I do?',
                        'answer' => 'Seek a safe space to express your feelings, whether through a trusted friend or counselor. Encourage open communication in the family, if possible.',
                    ],
                    [
                        'question' => 'What if your friends have turned you down and lowered your self-esteem? How do you bounce back?',
                        'answer' => 'Focus on self-care and affirmations. Surround yourself with supportive friends and engage in activities that build your confidence.',
                    ],
                    [
                        'question' => 'When people start talking about you, what can you do as a follower of Jesus?',
                        'answer' => 'Turn to prayer for strength and wisdom. Focus on your identity in Christ, and consider discussing your feelings with someone you trust.',
                    ],
                    [
                        'question' => 'When my friends leave or forsake me, I always feel as if I have come to the end of my life. What should I do?',
                        'answer' => "It's important to acknowledge your feelings but also to remember that your worth is not defined by others. Seek comfort in God’s presence and spend time with those who uplift you.",
                    ],
                    [
                        'question' => 'How do I let go of friends who do not help me?',
                        'answer' => 'Reflect on your relationships and set boundaries. It’s okay to distance yourself from those who bring negativity into your life.',
                    ],
                    [
                        'question' => 'I live with an aunt who’s mistreating me. How can I go about it?',
                        'answer' => 'Document the mistreatment and seek help from a trusted adult or counselor. You deserve to be in a safe and supportive environment.',
                    ],
                    [
                        'question' => 'One of my parents is having an affair, but the other doesn’t know, and they asked me to keep quiet. What should I do?',
                        'answer' => 'This is a complex situation. It’s important to seek guidance from a trusted adult or counsellor who can help you navigate your feelings and responsibilities.',
                    ],
                    [
                        'question' => 'Is it important to have friendships among students?',
                        'answer' => 'Yes, friendships among students are essential as they signify unity and support, reflecting God’s design for community (Genesis 1:27).',
                    ],
                    [
                        'question' => 'What does the Bible say about the importance of unity?',
                        'answer' => 'Psalms 133:1 emphasises the blessing of unity among people, indicating that togetherness is vital for a harmonious community.',
                    ],
                    [
                        'question' => 'How can I become a peacemaker in my friendships?',
                        'answer' => 'You can become a peacemaker by intentionally promoting harmony and understanding among peers, as instructed in Matthew 5:9.',
                    ],
                    [
                        'question' => 'What are the benefits of having strong friendships?',
                        'answer' => 'Strong friendships provide emotional support, accountability, and encouragement, as highlighted in Ecclesiastes 4:9-12.',
                    ],
                    [
                        'question' => 'How should I categorize my friendships?',
                        'answer' => "It's wise to categorize friendships based on closeness and purpose, similar to how Jesus had the twelve disciples and a closer inner circle.",
                    ],
                    [
                        'question' => 'How can I seek God’s guidance in choosing my friends?',
                        'answer' => 'Pray intentionally for God to choose your friends for you, as suggested in Proverbs 12:26.',
                    ],
                    [
                        'question' => 'What should I do about friends who discourage me?',
                        'answer' => 'Seek wisdom from God on how to deal with discouraging friends, and consider discussing your feelings with trusted individuals (Nehemiah 4:1-6).',
                    ],
                    [
                        'question' => 'What is the significance of covenant friendships?',
                        'answer' => 'Covenant friendships, like that of David and Jonathan, are deeply rooted in loyalty and support, providing a strong foundation for spiritual growth and encouragement (1 Samuel 20:1-23).',
                    ],
                    [
                        'question' => 'How can I deal with hurt from friends?',
                        'answer' => 'Pray for those who hurt you and ask the Holy Spirit to help you find healing and forgiveness (Romans 12:14).',
                    ],
                    [
                        'question' => 'How do I ensure my friendships point others to Christ?',
                        'answer' => 'Strive to reflect Christ’s love and teachings in your interactions and encourage your friends to do the same (Titus 2:11-14).',
                    ],
                ],
            ],
            // Sexual Purity
            [
                'category' => 'Sexual Purity',
                'entries' => [
                    [
                        'question' => 'Can I have a girlfriend/boyfriend in high school if we’re not doing anything bad?',
                        'answer' => 'No, high school is a time for personal growth and self-discovery. Engaging in relationships at this age is a distraction from focusing on your purpose. The Bible says we should not conform to worldly patterns but be transformed by God’s truth (Romans 12:2).',
                    ],
                    [
                        'question' => 'Why should I avoid opposite-sex relationships in high school?',
                        'answer' => 'Relationships at this age can easily lead to emotional distractions and unnecessary complications. High school is a season for self-discovery, and it’s important not to awaken emotions prematurely (Song of Solomon 8:4).',
                    ],
                    [
                        'question' => 'How can I avoid relationships while in a mixed school?',
                        'answer' => 'It’s a personal choice to serve as a Christian example in mixed schools. The Bible assures that no temptation is beyond what you can bear, and God will provide a way of escape (1 Corinthians 10:13).',
                    ],
                    [
                        'question' => 'How did you avoid relationships while in high school?',
                        'answer' => 'It’s essential to stay grounded in God’s word. Avoid focusing on struggles but instead emphasize God’s power to keep you pure. Stay away from situations and relationships that could lead you astray (2 Corinthians 6:17-18).',
                    ],
                    [
                        'question' => 'What if someone who has been good to me now wants sex?',
                        'answer' => 'No matter what they have done for you, saying “no” is the right choice. God has a purpose for you that was established before you ever met this person (Jeremiah 29:11). Your body is God’s temple, and you should honour Him with it (1 Corinthians 6:19-20).',
                    ],
                    [
                        'question' => 'What should I do if I feel pressured to have sex because “everyone is doing it”?',
                        'answer' => 'Peer pressure can be intense, but remember that God has called you to a different standard. The fear of man is a snare, but trusting God will keep you safe (Proverbs 29:25). Stand firm in your faith and choose to please God rather than people.',
                    ],
                    [
                        'question' => 'Is it wrong to be curious about sex and want to try it once?',
                        'answer' => 'Curiosity about sex is normal, but acting on that curiosity outside of marriage goes against God’s plan. The Bible calls us to self-control and godly living (Titus 2:12). Sexual purity brings honour to God and protects your future.',
                    ],
                    [
                        'question' => 'How can I deal with the guilt of having engaged in sex?',
                        'answer' => 'If you’ve made a mistake, remember that there is no condemnation for those in Christ Jesus (Romans 8:1). God’s forgiveness is available, and He will restore you if you repent. Turn to Him for strength and healing (1 John 1:9).',
                    ],
                    [
                        'question' => 'How can I break free from same-sex attraction (LGBTQ)?',
                        'answer' => 'Same-sex attraction is addressed in the Bible as contrary to God’s design (Romans 1:26-27). The first step is to come to Jesus, repent, and seek deliverance. God’s power can transform and renew your life.',
                    ],
                    [
                        'question' => 'What if I was lured into lesbianism/gay behaviour and can’t stop?',
                        'answer' => 'God’s grace is available to help you overcome. Sexual sin can feel overwhelming, but God’s word brings freedom. Accepting Jesus and staying close to Him will give you strength to overcome (Romans 7:24-25).',
                    ],
                    [
                        'question' => 'How can I help a friend who identifies as LGBTQ?',
                        'answer' => 'Be loving but stand firm on God’s word. Pray for your friend and share God’s truth with them. Help them understand that God has a better plan for their lives, one that leads to righteousness and eternal life (1 Corinthians 6:9-11).',
                    ],
                    [
                        'question' => 'Why does the Bible say homosexuality is wrong?',
                        'answer' => 'The Bible defines homosexuality as a deviation from God’s natural design for sexuality (Romans 1:26-27). God created sex to be enjoyed in the context of marriage between a man and a woman. Those who practice homosexuality are called to repentance.',
                    ],
                    [
                        'question' => 'Can God forgive me if I’ve already had sex?',
                        'answer' => 'Yes, God is merciful and forgives all sins when we repent (1 John 1:9). His grace is sufficient to restore you. There is no sin too great for God’s forgiveness, and He desires to heal and make you whole again.',
                    ],
                    [
                        'question' => 'How can I recover from sexual sins like pregnancy, STDs, or abortion?',
                        'answer' => 'Healing and restoration come through repentance and accepting God’s love and forgiveness. God is not here to condemn you but to set you free from guilt and shame (Romans 8:1). His love can restore your life and bring new purpose.',
                    ],
                    [
                        'question' => 'What should I do if I feel trapped in a cycle of sexual sin?',
                        'answer' => 'You can break free by surrendering your life to Jesus. Turn to Him for strength, confess your sins, and ask for the Holy Spirit’s help. The Bible promises that God will deliver you when you seek Him earnestly (Romans 7:24-25).',
                    ],
                    [
                        'question' => 'How do I overcome the constant temptation to go back to old habits?',
                        'answer' => 'The key is to avoid bad company and stay rooted in God’s word (1 Corinthians 15:33). Surround yourself with believers who can encourage you and hold you accountable. Strength comes from abiding in Christ and daily renewing your mind with scripture (Romans 12:2).',
                    ],
                ],
            ],
            // Academic Excellence
            [
                'category' => 'Academic Excellence',
                'entries' => [
                    [
                        'question' => 'Can someone do two courses in campus?',
                        'answer' => 'Yes, it is possible to pursue two courses in campus, though it requires careful time management and dedication. Ensure that the courses do not have overlapping schedules, and consider the workload and your ability to balance both. You can also pray for guidance and strength (Philippians 4:13).',
                    ],
                    [
                        'question' => 'What should I consider when selecting a course or career?',
                        'answer' => "Consider your passions, strengths, and God's purpose for your life. Proverbs 3:5-6 reminds us to acknowledge the Lord in all our ways, and He will direct our paths. Research the requirements for the course, such as subjects and grades needed, and align them with your long-term goals.",
                    ],
                    [
                        'question' => 'If I want to become a journalist, what subjects are mandatory, and what grade should I aim for?',
                        'answer' => 'To pursue journalism, subjects such as English and Kiswahili are key, with an emphasis on strong communication skills. Most universities require a minimum C+ or above in your national exams, but it’s important to research the specific cut-off points for the institution you are interested in.',
                    ],
                    [
                        'question' => 'What can I do to avoid developing a negative attitude towards certain subjects?',
                        'answer' => 'Shift your mindset by focusing on the value of the subject in your overall education. Pray for understanding and perseverance (James 1:5), seek support from teachers and classmates, and create a study plan that allows you to break down difficult topics.',
                    ],
                    [
                        'question' => 'What is the importance of setting academic goals?',
                        'answer' => 'Setting academic goals helps you stay focused, organized, and motivated. SMART goals (Specific, Measurable, Achievable, Relevant, Time-bound) enable you to track progress and adjust strategies as needed. Goal setting aligns with biblical principles of working diligently (Proverbs 21:5).',
                    ],
                    [
                        'question' => 'How can I manage stress related to my academic workload?',
                        'answer' => 'Manage stress by planning your time well, taking breaks, and incorporating spiritual practices such as prayer and meditation (Psalm 46:10). Create a balanced schedule that includes study, rest, and moments of reflection to maintain your mental and spiritual well-being.',
                    ],
                    [
                        'question' => 'What can one do to have a good relationship with teachers?',
                        'answer' => 'Show respect, communicate openly, and seek guidance from your teachers when needed. Developing a relationship built on integrity (Proverbs 11:3) can foster mutual respect and improve your learning experience.',
                    ],
                    [
                        'question' => 'How can I avoid being influenced by peer pressure while maintaining good relationships?',
                        'answer' => 'Surround yourself with like-minded individuals who share your values and spiritual goals (Proverbs 27:17). Pray for wisdom in your interactions, and set clear boundaries when it comes to negative influences like drugs, illicit relationships, and other distractions.',
                    ],
                ],
            ],
            // Mental Health
            [
                'category' => 'Mental Health',
                'entries' => [
                    [
                        'question' => 'What does my Christianity say about mental health?',
                        'answer' => <<<'EOT'
                        “Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God. And the peace of God, which transcends all understanding, will guard your hearts and your minds in Christ Jesus.” (Philippians 4:6-7)
                        
                        This verse shows that God cares for both our minds and hearts, encouraging us to seek His peace in anxious times.
                        EOT,
                    ],
                    [
                        'question' => 'Is it a sin to feel depressed or anxious?',
                        'answer' => <<<'EOT'
                        “Cast all your anxiety on him because he cares for you.” (1 Peter 5:7)
                        
                        Feelings of anxiety and depression are not sins, but opportunities to lean on God and trust His care.
                        EOT,
                    ],
                    [
                        'question' => 'Why is mental health important in my spiritual life?',
                        'answer' => <<<'EOT'
                        “Above all else, guard your heart, for everything you do flows from it.” (Proverbs 4:23)
                        
                        Mental health affects the heart and mind, which influences every aspect of life, including our spiritual walk.
                        EOT,
                    ],
                    [
                        'question' => 'How do I balance my faith with seeking therapy or counselling?',
                        'answer' => <<<'EOT'
                        “Where there is no guidance, a people falls, but in an abundance of counselors there is safety.” (Proverbs 11:14)
                        
                        Seeking professional help is wise, and God has provided counselors and experts to guide us through difficult times.
                        EOT,
                    ],
                    [
                        'question' => 'Can prayer or faith alone heal my mental health struggles, or do I need professional help?',
                        'answer' => <<<'EOT'
                        “The Lord is close to the brokenhearted and saves those who are crushed in spirit.” (Psalm 34:18)
                        
                        While faith and prayer bring healing, God also works through others, including mental health professionals, to bring healing to our brokenness.
                        EOT,
                    ],
                    [
                        'question' => 'If I’m struggling with my mental health, does that mean I don’t trust God enough?',
                        'answer' => <<<'EOT'
                        “The Lord is my light and my salvation—whom shall I fear? The Lord is the stronghold of my life—of whom shall I be afraid?” (Psalm 27:1)
                        
                        Trusting God doesn’t mean we won't face difficulties. It's a reminder that, even in struggles, God is still with us and offers strength.
                        EOT,
                    ],
                    [
                        'question' => 'How can I strengthen my faith when I’m feeling overwhelmed or anxious?',
                        'answer' => <<<'EOT'
                        “Come to me, all you who are weary and burdened, and I will give you rest.” (Matthew 11:28)
                        
                        Jesus invites us to bring our burdens to Him and find rest in His presence, strengthening our faith in times of anxiety.
                        EOT,
                    ],
                    [
                        'question' => 'What can I do if I feel disconnected from my faith during difficult times?',
                        'answer' => <<<'EOT'
                        “Draw near to God, and he will draw near to you.” (James 4:8)
                        
                        Even when feeling disconnected, God promises to be near when we seek Him, reminding us to keep drawing close.
                        EOT,
                    ],
                    [
                        'question' => 'Does God understand when I feel mentally or emotionally weak?',
                        'answer' => <<<'EOT'
                        “For we do not have a high priest who is unable to empathize with our weaknesses, but we have one who has been tempted in every way, just as we are—yet he did not sin.” (Hebrews 4:15)
                        
                        Jesus understands our struggles deeply, including emotional and mental weaknesses, because He has experienced them too.
                        EOT,
                    ],
                    [
                        'question' => 'Can my religious community support me if I’m struggling with mental health?',
                        'answer' => <<<'EOT'
                        “Carry each other’s burdens, and in this way you will fulfill the law of Christ.” (Galatians 6:2)
                        
                        The Christian community is called to support and care for one another, offering emotional and spiritual help in times of need.
                        EOT,
                    ],
                    [
                        'question' => 'How do I deal with guilt or shame related to my mental health challenges?',
                        'answer' => <<<'EOT'
                        “Therefore, there is now no condemnation for those who are in Christ Jesus.” (Romans 8:1)
                        
                        Guilt and shame can be overcome in Christ. He offers forgiveness and freedom from condemnation, no matter the struggles we face.
                        EOT,
                    ],
                    [
                        'question' => 'Is it wrong to take medication for mental health issues, or should I rely solely on my faith?',
                        'answer' => <<<'EOT'
                        “Every good gift and every perfect gift is from above, coming down from the Father of lights…” (James 1:17)
                        
                        Medicine can be a good gift from God, helping us manage mental health and providing healing when needed.
                        EOT,
                    ],
                    [
                        'question' => 'How can I pray when I don’t feel like praying because of my mental health struggles?',
                        'answer' => <<<'EOT'
                        “In the same way, the Spirit helps us in our weakness. We do not know what we ought to pray for, but the Spirit himself intercedes for us through wordless groans.” (Romans 8:26)
                        
                        Even when we feel unable to pray, the Holy Spirit helps us, expressing our deep needs when words are hard to find.
                        EOT,
                    ],
                    [
                        'question' => 'What does my faith say about self-care and taking care of my mind?',
                        'answer' => <<<'EOT'
                        “Do you not know that your bodies are temples of the Holy Spirit, who is in you, whom you have received from God? You are not your own; you were bought at a price. Therefore honor God with your bodies.” (1 Corinthians 6:19-20)
                        
                        Taking care of your mind and body is an important way to honor God, as our whole being belongs to Him.
                        EOT,
                    ],
                    [
                        'question' => 'How do I forgive myself if my mental health issues affect my relationships or spiritual life?',
                        'answer' => <<<'EOT'
                        “If we confess our sins, he is faithful and just and will forgive us our sins and purify us from all unrighteousness.” (1 John 1:9)
                        
                        Forgiveness is available to us through Christ, and we are called to forgive ourselves as God forgives us.
                        EOT,
                    ],
                    [
                        'question' => 'How can I maintain hope and trust in God during tough mental health moments?',
                        'answer' => <<<'EOT'
                        “But those who hope in the Lord will renew their strength. They will soar on wings like eagles; they will run and not grow weary, they will walk and not be faint.” (Isaiah 40:31)
                        
                        Even in challenging moments, placing hope in God renews our strength and gives us endurance.
                        EOT,
                    ],
                    [
                        'question' => 'Is it okay to talk to a therapist or counsellor if I feel like it conflicts with my beliefs?',
                        'answer' => <<<'EOT'
                        “Plans fail for lack of counsel, but with many advisers they succeed.” (Proverbs 15:22)
                        
                        Seeking wise counsel is a biblical principle. It’s okay to seek help from professionals, including Christian counsellors when needed.
                        EOT,
                    ],
                    [
                        'question' => 'How can I help my friends or family who are struggling with mental health and might feel uncomfortable seeking help because of their faith?',
                        'answer' => <<<'EOT'
                        “Carry each other’s burdens, and in this way you will fulfill the law of Christ.” (Galatians 6:2)
                        
                        Offer love and support without judgment, helping them see that seeking help is part of caring for themselves and following Christ's example.
                        EOT,
                    ],
                    [
                        'question' => 'How do I reconcile my religious beliefs with mental health treatments like therapy or medication?',
                        'answer' => <<<'EOT'
                        “But I will restore you to health and heal your wounds,’ declares the Lord…” (Jeremiah 30:17)
                        
                        God is a healer, and He can work through therapy, medication, and other treatments as part of His plan for restoration.
                        EOT,
                    ],
                    [
                        'question' => 'Can practising mindfulness or meditation conflict with my religious beliefs?',
                        'answer' => <<<'EOT'
                        “Be still, and know that I am God.” (Psalm 46:10)
                        
                        While mindfulness and meditation in certain forms may not align with Christian beliefs, being still and meditating on God's Word is encouraged and can help with mental well-being.
                        EOT,
                    ],
                ],
            ],

            // Drug & Substance Abuse
            [
                'category' => 'Drug & Substance Abuse',
                'entries' => [
                    [
                        'question' => 'What is alcohol or drug abuse?',
                        'answer' => <<<'EOT'
                        Alcohol or drug abuse is when someone uses substances in a way that harms their body, mind, or relationships. The Bible teaches that our bodies are temples of the Holy Spirit (1 Corinthians 6:19-20), and abusing alcohol or drugs can hurt both our physical and spiritual well-being.
                        EOT,
                    ],
                    [
                        'question' => 'Why do people start drinking or using drugs?',
                        'answer' => <<<'EOT'
                        People may start using alcohol or drugs out of curiosity, peer pressure, or as a way to cope with stress or emotions. However, God calls us to find our comfort and strength in Him (Philippians 4:13). It’s important to seek God’s guidance rather than relying on substances to deal with life’s challenges.
                        EOT,
                    ],
                    [
                        'question' => 'What are the risks of drinking alcohol or using drugs at my age?',
                        'answer' => <<<'EOT'
                        Drinking alcohol or using drugs at your age can impair your judgment and decision-making, and it may lead to physical and mental health problems. The Bible teaches us to avoid things that can enslave us (1 Corinthians 6:12), and substances like alcohol and drugs can quickly take control of our lives.
                        EOT,
                    ],
                    [
                        'question' => 'How can I tell if I have a problem with alcohol or drugs?',
                        'answer' => <<<'EOT'
                        If you find yourself regularly thinking about alcohol or drugs, or if you struggle to stop using them even when you know it’s hurting you or others, it could be a sign of a problem. The Bible says that we should be sober-minded and alert (1 Peter 5:8). If substances are affecting your ability to think clearly and make wise choices, it may be time to seek help.
                        EOT,
                    ],
                    [
                        'question' => 'Can drinking alcohol or using drugs affect my brain?',
                        'answer' => <<<'EOT'
                        Yes, alcohol and drugs can negatively affect the brain, impairing judgment, memory, and emotional regulation. The Bible reminds us that our minds are important, and we are called to renew them in Christ (Romans 12:2). When we use substances that harm our brains, it becomes harder to live out God’s will for our lives.
                        EOT,
                    ],
                    [
                        'question' => 'Is it normal for people my age to experiment with alcohol or drugs?',
                        'answer' => <<<'EOT'
                        It’s true that some people experiment with substances, but as Christians, we are called to live differently (Romans 12:2). The Bible teaches that we should avoid the things of this world that lead us into temptation. You don’t have to follow the crowd; instead, seek to honour God in all areas of your life, including your choices around alcohol and drugs (1 Corinthians 10:31).
                        EOT,
                    ],
                    [
                        'question' => 'How do I know if someone I know has an addiction to alcohol or drugs?',
                        'answer' => <<<'EOT'
                        Addiction can show itself in patterns of behaviour like using substances regularly, neglecting responsibilities, or continuing to use despite negative consequences. The Bible encourages us to care for one another (Galatians 6:2) and to offer help, support, and prayer for those who are struggling.
                        EOT,
                    ],
                    [
                        'question' => 'What should I do if I feel pressured to drink or use drugs?',
                        'answer' => <<<'EOT'
                        It’s okay to say no, and you can do so confidently, knowing that God calls you to live a holy life (1 Peter 1:16). You don’t have to explain yourself or give in to peer pressure. Stand firm in your faith and in the knowledge that God gives you strength to resist temptation (1 Corinthians 10:13).
                        EOT,
                    ],
                    [
                        'question' => 'Is it possible to become addicted to alcohol or drugs even if I only use them occasionally?',
                        'answer' => <<<'EOT'
                        Yes, addiction can develop even with occasional use. The Bible warns us that nothing should have control over us (1 Corinthians 6:12). Even small amounts of alcohol or drugs can lead to a loss of control and a stronger desire to continue using them.
                        EOT,
                    ],
                    [
                        'question' => 'Can drinking alcohol or using drugs affect my mental health?',
                        'answer' => <<<'EOT'
                        Yes, alcohol and drugs can negatively impact your mental health by increasing anxiety, depression, and other emotional struggles. The Bible teaches that we should have the peace of God guarding our hearts and minds (Philippians 4:7), and using substances can disrupt that peace.
                        EOT,
                    ],
                    [
                        'question' => 'How do I talk to my parents or a trusted adult about alcohol or drug use?',
                        'answer' => <<<'EOT'
                        Approach the conversation with honesty and respect, knowing that your parents or trusted adult will likely want to help and support you. The Bible encourages us to honour our parents (Ephesians 6:1-3), and part of that is being open about struggles and seeking guidance.
                        EOT,
                    ],
                    [
                        'question' => 'What should I do if my friend is using drugs or drinking too much?',
                        'answer' => <<<'EOT'
                        Be there for your friend and encourage them to get help. The Bible tells us to speak the truth in love (Ephesians 4:15), so you can gently guide your friend toward healthier choices and offer support. You might also want to pray for them, asking God to help them find freedom from substance use.
                        EOT,
                    ],
                    [
                        'question' => 'How can I say no to alcohol or drugs without feeling awkward or judged?',
                        'answer' => <<<'EOT'
                        It’s normal to feel awkward, but remember that your worth and identity are in Christ, not in what others think (Galatians 1:10). You can say no confidently, knowing that you are honouring God with your body and your choices. True friends will respect your decision.
                        EOT,
                    ],
                    [
                        'question' => 'What are the long-term effects of alcohol and drug abuse on my body?',
                        'answer' => <<<'EOT'
                        Alcohol and drug abuse can lead to serious long-term health problems like liver damage, heart issues, lung problems, and mental health disorders. God calls us to honour Him with our bodies (1 Corinthians 6:19-20), and substance abuse is not honouring God or yourself.
                        EOT,
                    ],
                    [
                        'question' => 'Is it okay to drink alcohol or use drugs in moderation?',
                        'answer' => <<<'EOT'
                        While the Bible doesn’t say that drinking alcohol is always wrong, it does warn against drunkenness (Ephesians 5:18). It’s important to be wise about the impact alcohol and drugs have on your life and your relationship with God. Moderation is not the goal—living a life that honours God is the goal.
                        EOT,
                    ],
                    [
                        'question' => 'How can I help someone who is addicted to alcohol or drugs?',
                        'answer' => <<<'EOT'
                        You can encourage your friend to seek professional help, offer prayer and support, and remind them that God offers healing and strength (Isaiah 41:10). Be there to walk alongside them in their journey, but remember that recovery often requires help from trained professionals.
                        EOT,
                    ],
                    [
                        'question' => 'How do alcohol and drugs impact my relationships with others?',
                        'answer' => <<<'EOT'
                        Alcohol and drugs can damage relationships by causing people to act irresponsibly, hurt others, or create distrust. The Bible teaches us to love one another and maintain healthy relationships (John 13:34-35). Substances can often get in the way of these relationships.
                        EOT,
                    ],
                    [
                        'question' => 'What happens if I get caught drinking or using drugs?',
                        'answer' => <<<'EOT'
                        Getting caught may lead to consequences like damaging your reputation, harming relationships, and experiencing legal or academic trouble. The Bible encourages us to live honourably and avoid things that bring shame (1 Timothy 3:2). It's important to think about the long-term impact of your choices.
                        EOT,
                    ],
                    [
                        'question' => 'How does peer pressure affect my choices about alcohol or drugs?',
                        'answer' => <<<'EOT'
                        Peer pressure can make you feel like you should conform to others, but as a Christian, you are called to stand apart and live a life that reflects Christ (Romans 12:2). Trust that God has given you the strength to resist temptation and rely on Him to help you make wise choices.
                        EOT,
                    ],
                    [
                        'question' => 'What are some healthy ways to cope with stress instead of using alcohol or drugs?',
                        'answer' => <<<'EOT'
                        Prayer, reading the Bible, spending time with supportive friends, exercising, and pursuing hobbies are all great ways to cope with stress. The Bible says that God is our refuge and strength (Psalm 46:1), so turning to Him for comfort and guidance is the best way to handle life’s challenges.
                        EOT,
                    ],
                ],
            ],

        ];

        $categories = MissionFaqCategory::all();

        foreach ($faqs as $faq) {
            $category = $categories->where('name', $faq['category'])->first();

            foreach ($faq['entries'] as $entry) {
                MissionFaq::updateOrCreate([
                    'question' => $entry['question'],
                ], [
                    'question' => $entry['question'],
                    'answer' => $entry['answer'],
                    'mission_faq_category_id' => $category->id,
                ]);
            }
        }
    }
}
