--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9
-- Dumped by pg_dump version 16.5

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: shipment_orders; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.shipment_orders (
    id integer NOT NULL,
    order_type character varying(20) NOT NULL,
    status character varying(20) DEFAULT 'new'::character varying,
    pickup_address text NOT NULL,
    ready_time time without time zone,
    cargo_type character varying(100),
    weight numeric(10,2),
    dimensions character varying(100),
    contact_name character varying(100),
    contact_phone character varying(20),
    pickup_city character varying(100),
    destination_city character varying(100),
    delivery_address text,
    delivery_method character varying(50),
    desired_arrival_date date,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    notes text,
    recipient_contact character varying(100),
    recipient_phone character varying(20),
    comment text,
    CONSTRAINT shipment_orders_order_type_check CHECK (((order_type)::text = ANY ((ARRAY['astana'::character varying, 'regional'::character varying])::text[]))),
    CONSTRAINT shipment_orders_status_check CHECK (((status)::text = ANY ((ARRAY['new'::character varying, 'processing'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])))
);


ALTER TABLE public.shipment_orders OWNER TO neondb_owner;

--
-- Name: shipment_orders_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.shipment_orders_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shipment_orders_id_seq OWNER TO neondb_owner;

--
-- Name: shipment_orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.shipment_orders_id_seq OWNED BY public.shipment_orders.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: neondb_owner
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(50) NOT NULL,
    password character varying(255) NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.users OWNER TO neondb_owner;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: neondb_owner
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO neondb_owner;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: neondb_owner
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: shipment_orders id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.shipment_orders ALTER COLUMN id SET DEFAULT nextval('public.shipment_orders_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: shipment_orders; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.shipment_orders (id, order_type, status, pickup_address, ready_time, cargo_type, weight, dimensions, contact_name, contact_phone, pickup_city, destination_city, delivery_address, delivery_method, desired_arrival_date, created_at, updated_at, notes, recipient_contact, recipient_phone, comment) FROM stdin;
2	regional	processing	ул. Кунаева 12	10:00:00	Электроника	5.00	50x40x30			Астана	Алматы	ул. Абая 150	Самовывоз	2025-07-20	2025-07-17 12:47:57.080818	2025-07-18 04:04:00.23849	Хрупкий груз	\N	\N	\N
1	astana	processing	ул. Абая 10	14:00:00	Документы	1.50	A4			\N	\N	\N	\N	\N	2025-07-17 12:47:49.041202	2025-07-18 04:04:12.608011	Тестовый заказ	\N	\N	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: neondb_owner
--

COPY public.users (id, username, password, created_at, updated_at) FROM stdin;
1	admin	$2y$10$WMfHFgagrt1yMLVT6GdrHejr29JQoBW9Q27nfYDxJe5b9rXTy/9JC	2025-07-17 12:47:11.658518	2025-07-18 04:05:06.789617
\.


--
-- Name: shipment_orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.shipment_orders_id_seq', 2, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: neondb_owner
--

SELECT pg_catalog.setval('public.users_id_seq', 1, true);


--
-- Name: shipment_orders shipment_orders_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.shipment_orders
    ADD CONSTRAINT shipment_orders_pkey PRIMARY KEY (id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: neondb_owner
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: cloud_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE cloud_admin IN SCHEMA public GRANT ALL ON SEQUENCES TO neon_superuser WITH GRANT OPTION;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: cloud_admin
--

ALTER DEFAULT PRIVILEGES FOR ROLE cloud_admin IN SCHEMA public GRANT ALL ON TABLES TO neon_superuser WITH GRANT OPTION;


--
-- PostgreSQL database dump complete
--

